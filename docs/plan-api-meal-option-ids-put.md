# Action Plan: Support `meal_option_ids` in `PUT /api/meals/{id}`

**Date:** 2026-02-24  
**Priority:** High  
**Context:** When the user adds more than one meal option for the same meal time in a day, the frontend now calls `PUT /api/meals/{id}` (instead of `POST /api/meals`) sending a full replacement list via `meal_option_ids`. The current Symfony backend ignores this field in the PUT handler, so changes are silently lost.

---

## Problem Summary

| Layer       | Current behaviour                                                                                          | Expected behaviour                                                                 |
|-------------|-----------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------|
| Frontend    | ~~Always called POST, creating duplicate `Meal` rows per day~~ — **Fixed (2026-02-24)**                   | Calls PUT with full `meal_option_ids` list when a `Meal` already exists for the day |
| Mock API    | PUT handler ignores `meal_option_ids`                                                                     | PUT handler syncs `meal_times` from the new `meal_option_ids` list                  |
| Symfony API | PUT handler (`MealController::update`) ignores `meal_option_ids`                                          | PUT handler fully replaces the `MealOption` collection on the `Meal` entity         |

---

## Scope of Changes

### 1. Symfony API — `api/` directory (inside Docker container)

#### 1.1 Entity relationship check

Verify that `Meal` has a `ManyToMany` (or `OneToMany` via join entity) relationship with `MealOption`.  
Expected location: `api/app/src/Entity/Meal.php`

```
// Expected relationship (ManyToMany example):
#[ORM\ManyToMany(targetEntity: MealOption::class)]
#[ORM\JoinTable(name: 'meal_meal_option')]
private Collection $mealOptions;
```

If the relationship is `OneToMany` through an intermediate entity (e.g., `MealEntry`), adapt the steps below accordingly.

#### 1.2 Modify `MealController::update` (PUT handler)

**File:** `api/app/src/Controller/MealController.php`  
**Method:** The method handling `PUT /api/meals/{id}` (likely `update` or `edit`).

Add the following block **after** the existing field updates (`name`, `calories`, `date`, `notes`) and **before** `$em->flush()`:

```php
// Sync meal_option_ids if provided (full replacement)
if (array_key_exists('meal_option_ids', $data)) {
    // Clear current associations
    $meal->getMealOptions()->clear();

    foreach ((array) $data['meal_option_ids'] as $optId) {
        /** @var \App\Entity\MealOption|null $option */
        $option = $em->getRepository(\App\Entity\MealOption::class)->find((int) $optId);
        if ($option !== null) {
            $meal->addMealOption($option);
        }
    }
}
```

> **Note:** If the relationship is managed via an intermediate entity (e.g., `MealEntry`), replace the block above with the equivalent logic:
> 1. Delete all existing `MealEntry` rows for this `Meal`.
> 2. Create a new `MealEntry` for each `$optId` in `meal_option_ids`.
> 3. Persist each new entry.

#### 1.3 Verify `Meal::addMealOption` helper exists

**File:** `api/app/src/Entity/Meal.php`

Ensure the entity exposes `addMealOption` and `getMealOptions` methods. If they are missing, add them:

```php
public function getMealOptions(): Collection
{
    return $this->mealOptions;
}

public function addMealOption(MealOption $option): static
{
    if (!$this->mealOptions->contains($option)) {
        $this->mealOptions->add($option);
    }
    return $this;
}

public function removeMealOption(MealOption $option): static
{
    $this->mealOptions->removeElement($option);
    return $this;
}
```

#### 1.4 Verify serialisation — `GET /api/meals` response shape

The `GET /api/meals` response must return each meal with the `meal_times` structure (options grouped by meal time). Confirm the serialiser / normaliser includes the updated `mealOptions` collection after the PUT.

If the `GET` response is built from a dedicated query (e.g., with a JOIN), ensure the query re-fetches the association after flush — or call `$em->refresh($meal)` before serialising the response.

#### 1.5 Run inside Docker container

All Symfony commands must be run from inside the PHP container:

```bash
docker-compose exec php bash

# Inside the container:
php bin/console doctrine:schema:validate
php bin/console cache:clear
```

> If `doctrine:schema:validate` reports mapping errors related to the `MealOption` association, run a migration:
> ```bash
> php bin/console doctrine:migrations:diff
> php bin/console doctrine:migrations:migrate
> ```


## Acceptance Criteria

- [ ] `PUT /api/meals/{id}` with body `{ "meal_option_ids": [1, 3] }` responds `200` and the subsequent `GET /api/meals` shows both options grouped under their respective `meal_time` in the `meal_times` array.
- [ ] `PUT /api/meals/{id}` without `meal_option_ids` continues to work normally (no regression on `name`, `calories`, `date`, `notes`).
- [ ] Adding a second option for the same meal time (e.g., two "desayuno" options) shows both chips in the day modal without duplicating the `Meal` row.
- [ ] Removing an option via the day modal correctly removes it and keeps the remaining options.
- [ ] `doctrine:schema:validate` passes without errors.
- [ ] No TypeScript errors on the frontend (`npx tsc --noEmit` exits 0). ✅ Already verified.

---
