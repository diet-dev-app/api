<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215196000_seed_meal_options_and_link_times extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed meal options and associate them with meal times.';
    }

    public function up(Schema $schema): void
    {
        // Map meal options to meal times
        $optionMap = [
            // desayuno
            ['Smoothie (fruta + proteína)', 'Ej.: arándanos/frutos rojos + whey', 'desayuno'],
            ['Bocadillo de atún', 'Pan integral + atún + tomate/verdura', 'desayuno'],
            ['Bol de avena con fruta', 'Avena + fruta + (opcional) crema cacahuete', 'desayuno'],
            ['Tortitas/arepas + proteína', 'Con huevo/claras; ajusta según tu plan', 'desayuno'],
            // comida
            ['Ensalada con pollo', 'Verdura libre + pechuga + (opcional) aguacate', 'comida'],
            ['Pasta con atún', 'Pasta integral + atún + verduras', 'comida'],
            ['Lentejas con pollo', 'Lentejas + pechuga + verduras', 'comida'],
            ['Poke bowl', 'Arroz + salmón + verduras + (opcional) frutos secos', 'comida'],
            ['Verdura con pescado', 'Verduras + pescado blanco; aceite medido', 'comida'],
            ['Ensalada de patata', 'Patata + huevo/claras + verduras', 'comida'],
            ['Ensalada de pasta', 'Pasta + proteína + verduras', 'comida'],
            // merienda
            ['Yogur con fruta', 'Fruta + yogur proteico/natural', 'merienda'],
            ['Queso fresco con fruta', 'Queso batido/queso fresco + fruta', 'merienda'],
            ['Batido con fruta', 'Fruta + whey + agua/leche según plan', 'merienda'],
            ['Cachas de avena', 'Avena + agua + whey (si aplica)', 'merienda'],
            // cena
            ['Tortilla de calabacín', 'Huevo/claras + verdura', 'cena'],
            ['Verduras con pollo', 'Verdura libre + pechuga + aceite medido', 'cena'],
            ['Ensalada con atún', 'Verdura + atún + aceite medido', 'cena'],
            ['Tortas de arroz con salmón', 'Tortas + salmón ahumado + aguacate (si toca)', 'cena'],
        ];
        foreach ($optionMap as [$name, $desc, $mealTimeName]) {
            $this->addSql('INSERT INTO meal_option (name, description, meal_time_id) VALUES (?, ?, (SELECT id FROM meal_time WHERE name = ?))', [$name, $desc, $mealTimeName]);
        }
    }

    public function down(Schema $schema): void
    {
        $names = [
            'Smoothie (fruta + proteína)',
            'Bocadillo de atún',
            'Bol de avena con fruta',
            'Tortitas/arepas + proteína',
            'Ensalada con pollo',
            'Pasta con atún',
            'Lentejas con pollo',
            'Poke bowl',
            'Verdura con pescado',
            'Ensalada de patata',
            'Ensalada de pasta',
            'Yogur con fruta',
            'Queso fresco con fruta',
            'Batido con fruta',
            'Cachas de avena',
            'Tortilla de calabacín',
            'Verduras con pollo',
            'Ensalada con atún',
            'Tortas de arroz con salmón',
        ];
        foreach ($names as $name) {
            $this->addSql('DELETE FROM meal_option WHERE name = ?', [$name]);
        }
    }
}
