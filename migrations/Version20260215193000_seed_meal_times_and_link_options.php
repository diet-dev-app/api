<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215193000_seed_meal_times_and_link_options extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed meal times and associate meal options to each time';
    }

    public function up(Schema $schema): void
    {
        // Insert meal times
        $mealTimes = [
            ['desayuno', 'Desayuno'],
            ['comida', 'Comida'],
            ['merienda', 'Merienda'],
            ['cena', 'Cena'],
        ];
        foreach ($mealTimes as [$name, $label]) {
            $this->addSql('INSERT INTO meal_time (name, label) VALUES (?, ?)', [$name, $label]);
        }

        // Map meal options to meal times
        $optionMap = [
            // desayuno
            ['Smoothie (fruta + proteína)', 'desayuno'],
            ['Bocadillo de atún', 'desayuno'],
            ['Bol de avena con fruta', 'desayuno'],
            ['Tortitas/arepas + proteína', 'desayuno'],
            // comida
            ['Ensalada con pollo', 'comida'],
            ['Pasta con atún', 'comida'],
            ['Lentejas con pollo', 'comida'],
            ['Poke bowl', 'comida'],
            ['Verdura con pescado', 'comida'],
            ['Ensalada de patata', 'comida'],
            ['Ensalada de pasta', 'comida'],
            // merienda
            ['Yogur con fruta', 'merienda'],
            ['Queso fresco con fruta', 'merienda'],
            ['Batido con fruta', 'merienda'],
            ['Cachas de avena', 'merienda'],
            // cena
            ['Tortilla de calabacín', 'cena'],
            ['Verduras con pollo', 'cena'],
            ['Ensalada con atún', 'cena'],
            ['Tortas de arroz con salmón', 'cena'],
        ];
        // Set meal_time_id in meal_option
        foreach ($optionMap as [$optionName, $mealTimeName]) {
            $this->addSql('UPDATE meal_option SET meal_time_id = (SELECT id FROM meal_time WHERE name = ?) WHERE name = ?', [$mealTimeName, $optionName]);
        }
    }

    public function down(Schema $schema): void
    {
        // Remove meal_time_id from meal_option
        $this->addSql('UPDATE meal_option SET meal_time_id = NULL');
        // Remove meal times
        $this->addSql('DELETE FROM meal_time');
    }
}
