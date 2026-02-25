<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents an ingredient belonging to a MealOption.
 * Each ingredient has a name, quantity and unit,
 * allowing calorie calculation via OpenAI.
 */
#[ORM\Entity(repositoryClass: IngredientRepository::class)]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MealOption::class, inversedBy: 'ingredients')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MealOption $mealOption = null;

    /** @var string Ingredient name (e.g. "chicken breast") */
    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    /** @var float Amount of the ingredient (e.g. 200) */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private float $quantity;

    /** @var string Unit of measurement (e.g. "g", "ml", "unit") */
    #[ORM\Column(type: 'string', length: 30)]
    private string $unit;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMealOption(): ?MealOption
    {
        return $this->mealOption;
    }

    public function setMealOption(?MealOption $mealOption): self
    {
        $this->mealOption = $mealOption;
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

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * Returns a human-readable representation for OpenAI prompts.
     */
    public function toText(): string
    {
        return "{$this->quantity} {$this->unit} of {$this->name}";
    }
}
