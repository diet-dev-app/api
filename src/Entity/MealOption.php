<?php

namespace App\Entity;

use App\Repository\MealOptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a meal option (e.g. "Grilled chicken salad") linked to a MealTime.
 * A MealOption holds its list of ingredients so calorie calculations can be
 * performed externally (e.g. via OpenAI).
 */
#[ORM\Entity(repositoryClass: MealOptionRepository::class)]
class MealOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MealTime::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?MealTime $mealTime = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Cached estimated calories calculated (e.g. by OpenAI).
     * Null until it has been calculated.
     */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?float $estimatedCalories = null;

    /** @var Collection<int, Ingredient> */
    #[ORM\OneToMany(mappedBy: 'mealOption', targetEntity: Ingredient::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $ingredients;

    public function __construct()
    {
        $this->ingredients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMealTime(): ?MealTime
    {
        return $this->mealTime;
    }

    public function setMealTime(?MealTime $mealTime): self
    {
        $this->mealTime = $mealTime;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getEstimatedCalories(): ?float
    {
        return $this->estimatedCalories !== null ? (float) $this->estimatedCalories : null;
    }

    public function setEstimatedCalories(?float $estimatedCalories): self
    {
        $this->estimatedCalories = $estimatedCalories;
        return $this;
    }

    /** @return Collection<int, Ingredient> */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function addIngredient(Ingredient $ingredient): self
    {
        if (!$this->ingredients->contains($ingredient)) {
            $this->ingredients->add($ingredient);
            $ingredient->setMealOption($this);
        }
        return $this;
    }

    public function removeIngredient(Ingredient $ingredient): self
    {
        if ($this->ingredients->removeElement($ingredient)) {
            if ($ingredient->getMealOption() === $this) {
                $ingredient->setMealOption(null);
            }
        }
        return $this;
    }
}
