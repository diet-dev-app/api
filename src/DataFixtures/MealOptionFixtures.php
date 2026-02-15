<?php

namespace App\DataFixtures;

use App\Entity\MealOption;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;



class MealOptionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $defaultOptions = [
            'desayuno' => [
                ['name' => 'Smoothie (fruta + proteína)', 'notes' => 'Ej.: arándanos/frutos rojos + whey'],
                ['name' => 'Bocadillo de atún', 'notes' => 'Pan integral + atún + tomate/verdura'],
                ['name' => 'Bol de avena con fruta', 'notes' => 'Avena + fruta + (opcional) crema cacahuete'],
                ['name' => 'Tortitas/arepas + proteína', 'notes' => 'Con huevo/claras; ajusta según tu plan'],
            ],
            'comida' => [
                ['name' => 'Ensalada con pollo', 'notes' => 'Verdura libre + pechuga + (opcional) aguacate'],
                ['name' => 'Pasta con atún', 'notes' => 'Pasta integral + atún + verduras'],
                ['name' => 'Lentejas con pollo', 'notes' => 'Lentejas + pechuga + verduras'],
                ['name' => 'Poke bowl', 'notes' => 'Arroz + salmón + verduras + (opcional) frutos secos'],
                ['name' => 'Verdura con pescado', 'notes' => 'Verduras + pescado blanco; aceite medido'],
                ['name' => 'Ensalada de patata', 'notes' => 'Patata + huevo/claras + verduras'],
                ['name' => 'Ensalada de pasta', 'notes' => 'Pasta + proteína + verduras'],
            ],
            'merienda' => [
                ['name' => 'Yogur con fruta', 'notes' => 'Fruta + yogur proteico/natural'],
                ['name' => 'Queso fresco con fruta', 'notes' => 'Queso batido/queso fresco + fruta'],
                ['name' => 'Batido con fruta', 'notes' => 'Fruta + whey + agua/leche según plan'],
                ['name' => 'Cachas de avena', 'notes' => 'Avena + agua + whey (si aplica)'],
            ],
            'cena' => [
                ['name' => 'Tortilla de calabacín', 'notes' => 'Huevo/claras + verdura'],
                ['name' => 'Verduras con pollo', 'notes' => 'Verdura libre + pechuga + aceite medido'],
                ['name' => 'Ensalada con atún', 'notes' => 'Verdura + atún + aceite medido'],
                ['name' => 'Tortas de arroz con salmón', 'notes' => 'Tortas + salmón ahumado + aguacate (si toca)'],
            ],
        ];

        foreach ($defaultOptions as $mealType => $options) {
            foreach ($options as $option) {
                $mealOption = new MealOption();
                $mealOption->setName($option['name']);
                $mealOption->setDescription($option['notes']);
                $manager->persist($mealOption);
            }
        }
        $manager->flush();
    }


}
