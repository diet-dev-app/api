<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215190000_seed_meal_options extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed default meal options as a global bank of options';
    }

    public function up(Schema $schema): void
    {
        $options = [
            // Desayuno
            ['Smoothie (fruta + proteína)', 'Ej.: arándanos/frutos rojos + whey'],
            ['Bocadillo de atún', 'Pan integral + atún + tomate/verdura'],
            ['Bol de avena con fruta', 'Avena + fruta + (opcional) crema cacahuete'],
            ['Tortitas/arepas + proteína', 'Con huevo/claras; ajusta según tu plan'],
            // Comida
            ['Ensalada con pollo', 'Verdura libre + pechuga + (opcional) aguacate'],
            ['Pasta con atún', 'Pasta integral + atún + verduras'],
            ['Lentejas con pollo', 'Lentejas + pechuga + verduras'],
            ['Poke bowl', 'Arroz + salmón + verduras + (opcional) frutos secos'],
            ['Verdura con pescado', 'Verduras + pescado blanco; aceite medido'],
            ['Ensalada de patata', 'Patata + huevo/claras + verduras'],
            ['Ensalada de pasta', 'Pasta + proteína + verduras'],
            // Merienda
            ['Yogur con fruta', 'Fruta + yogur proteico/natural'],
            ['Queso fresco con fruta', 'Queso batido/queso fresco + fruta'],
            ['Batido con fruta', 'Fruta + whey + agua/leche según plan'],
            ['Cachas de avena', 'Avena + agua + whey (si aplica)'],
            // Cena
            ['Tortilla de calabacín', 'Huevo/claras + verdura'],
            ['Verduras con pollo', 'Verdura libre + pechuga + aceite medido'],
            ['Ensalada con atún', 'Verdura + atún + aceite medido'],
            ['Tortas de arroz con salmón', 'Tortas + salmón ahumado + aguacate (si toca)'],
        ];
        foreach ($options as [$name, $desc]) {
            $this->addSql('INSERT INTO meal_option (name, description) VALUES (?, ?)', [$name, $desc]);
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
