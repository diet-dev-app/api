<?php

namespace App\Entity;

use App\Repository\MealRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MealRepository::class)]
class Meal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'meals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $calories;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToMany(targetEntity: MealOption::class)]
    #[ORM\JoinTable(name: 'meal_meal_option')]
    private $mealOptions;

    public function __construct()
    {
        $this->mealOptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    /**
     * @return \Doctrine\Common\Collections\Collection|MealOption[]
     */
    public function getMealOptions(): \Doctrine\Common\Collections\Collection
    {
        return $this->mealOptions;
    }

    public function addMealOption(MealOption $mealOption): self
    {
        if (!$this->mealOptions->contains($mealOption)) {
            $this->mealOptions[] = $mealOption;
        }
        return $this;
    }

    public function removeMealOption(MealOption $mealOption): self
    {
        $this->mealOptions->removeElement($mealOption);
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;
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

    public function getCalories(): int
    {
        return $this->calories;
    }
    public function setCalories(int $calories): self
    {
        $this->calories = $calories;
        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
}
