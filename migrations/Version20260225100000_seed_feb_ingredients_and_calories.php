<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed Plan Nutricional Febrero 2026:
 *  - Updates estimated_calories on existing meal_option rows.
 *  - Inserts ingredient rows linked to each option.
 *
 * Calorie estimates are calculated from the ingredient macros listed in the
 * nutritional plan (standard macro values: protein/carbs = 4 kcal/g, fat = 9 kcal/g).
 *
 * Existing February options (added by Version20260215196000):
 *  Desayuno: Smoothie (fruta + proteína), Bocadillo de atún, Bol de avena con fruta, Tortitas/arepas + proteína
 *  Comida:   Ensalada con pollo, Pasta con atún, Lentejas con pollo, Poke bowl, Verdura con pescado,
 *            Ensalada de patata, Ensalada de pasta
 *  Merienda: Yogur con fruta, Queso fresco con fruta, Batido con fruta, Cachas de avena
 *  Cena:     Tortilla de calabacín, Verduras con pollo, Ensalada con atún, Tortas de arroz con salmón
 */
final class Version20260225100000_seed_feb_ingredients_and_calories extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed February 2026 nutritional plan: add ingredients and calories to existing meal options.';
    }

    public function up(Schema $schema): void
    {
        // -------------------------------------------------------------------------
        // DESAYUNO – Opción 1: Smoothie (fruta + proteína) ~420 kcal
        // -------------------------------------------------------------------------
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 420.00 WHERE name = 'Smoothie (fruta + proteína)'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Bebida de arroz o leche desnatada', 200, 'ml' FROM meal_option WHERE name = 'Smoothie (fruta + proteína)'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Arándanos / frutos rojos', 40, 'g' FROM meal_option WHERE name = 'Smoothie (fruta + proteína)'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Whey protein', 30, 'g' FROM meal_option WHERE name = 'Smoothie (fruta + proteína)'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Claras de huevo', 200, 'ml' FROM meal_option WHERE name = 'Smoothie (fruta + proteína)'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Kiwi', 1, 'unit' FROM meal_option WHERE name = 'Smoothie (fruta + proteína)'"
        );

        // -------------------------------------------------------------------------
        // DESAYUNO – Opción 2: Bocadillo de atún ~480 kcal
        // -------------------------------------------------------------------------
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 480.00 WHERE name = 'Bocadillo de atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Pan integral / cristal / blanco', 75, 'g' FROM meal_option WHERE name = 'Bocadillo de atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Claras de huevo', 150, 'ml' FROM meal_option WHERE name = 'Bocadillo de atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Atún al natural', 1, 'lata' FROM meal_option WHERE name = 'Bocadillo de atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Kiwi', 1, 'unit' FROM meal_option WHERE name = 'Bocadillo de atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Aceite de oliva', 1, 'g' FROM meal_option WHERE name = 'Bocadillo de atún'"
        );

        // -------------------------------------------------------------------------
        // DESAYUNO – Opción 3: Bol de avena con fruta ~500 kcal
        // -------------------------------------------------------------------------
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 500.00 WHERE name = 'Bol de avena con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Avena / copos de maíz sin azúcar', 40, 'g' FROM meal_option WHERE name = 'Bol de avena con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Bebida de arroz o leche desnatada', 200, 'ml' FROM meal_option WHERE name = 'Bol de avena con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Arándanos / frutos rojos', 50, 'g' FROM meal_option WHERE name = 'Bol de avena con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Whey protein', 40, 'g' FROM meal_option WHERE name = 'Bol de avena con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Mantequilla de cacahuete', 10, 'g' FROM meal_option WHERE name = 'Bol de avena con fruta'"
        );

        // -------------------------------------------------------------------------
        // DESAYUNO – Opción 4: Tortitas/arepas + proteína ~450 kcal
        // -------------------------------------------------------------------------
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 450.00 WHERE name = 'Tortitas/arepas + proteína'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Arepa venezolana', 1, 'unit' FROM meal_option WHERE name = 'Tortitas/arepas + proteína'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Huevo entero', 1, 'unit' FROM meal_option WHERE name = 'Tortitas/arepas + proteína'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Claras de huevo', 200, 'ml' FROM meal_option WHERE name = 'Tortitas/arepas + proteína'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tomate / cebolla', 40, 'g' FROM meal_option WHERE name = 'Tortitas/arepas + proteína'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Kiwi', 1, 'unit' FROM meal_option WHERE name = 'Tortitas/arepas + proteína'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Queso light', 1, 'loncha' FROM meal_option WHERE name = 'Tortitas/arepas + proteína'"
        );

        // =========================================================================
        // COMIDA
        // =========================================================================

        // Opción 1: Ensalada con pollo ~400 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 400.00 WHERE name = 'Ensalada con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Pechuga de pollo', 160, 'g' FROM meal_option WHERE name = 'Ensalada con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Aguacate', 30, 'g' FROM meal_option WHERE name = 'Ensalada con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Ensalada (hoja verde al gusto)', 100, 'g' FROM meal_option WHERE name = 'Ensalada con pollo'"
        );

        // Opción 2: Pasta con atún ~520 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 520.00 WHERE name = 'Pasta con atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Crema de verduras', 2, 'cazo' FROM meal_option WHERE name = 'Pasta con atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Atún al natural', 1, 'lata' FROM meal_option WHERE name = 'Pasta con atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Frutos secos', 15, 'g' FROM meal_option WHERE name = 'Pasta con atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Pasta blanca cocida', 75, 'g' FROM meal_option WHERE name = 'Pasta con atún'"
        );

        // Opción 3: Lentejas con pollo ~580 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 580.00 WHERE name = 'Lentejas con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Crema de verduras', 2, 'cazo' FROM meal_option WHERE name = 'Lentejas con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Pechuga de pollo', 160, 'g' FROM meal_option WHERE name = 'Lentejas con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Lentejas cocidas', 75, 'g' FROM meal_option WHERE name = 'Lentejas con pollo'"
        );

        // Opción 4: Poke bowl ~620 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 620.00 WHERE name = 'Poke bowl'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Salmón', 160, 'g' FROM meal_option WHERE name = 'Poke bowl'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tomate', 1, 'unit' FROM meal_option WHERE name = 'Poke bowl'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Nueces', 20, 'g' FROM meal_option WHERE name = 'Poke bowl'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Cebolla / pimiento / calabacín', 50, 'g' FROM meal_option WHERE name = 'Poke bowl'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Aceite de oliva', 5, 'g' FROM meal_option WHERE name = 'Poke bowl'"
        );

        // Opción 5: Verdura con pescado ~420 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 420.00 WHERE name = 'Verdura con pescado'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Pescado blanco', 175, 'g' FROM meal_option WHERE name = 'Verdura con pescado'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tomate', 50, 'g' FROM meal_option WHERE name = 'Verdura con pescado'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Cebolla / pimiento', 50, 'g' FROM meal_option WHERE name = 'Verdura con pescado'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Huevo entero', 1, 'unit' FROM meal_option WHERE name = 'Verdura con pescado'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Nueces', 30, 'g' FROM meal_option WHERE name = 'Verdura con pescado'"
        );

        // Opción 6: Ensalada de patata ~500 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 500.00 WHERE name = 'Ensalada de patata'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Ensalada al gusto', 100, 'g' FROM meal_option WHERE name = 'Ensalada de patata'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tomate cherry', 3, 'unit' FROM meal_option WHERE name = 'Ensalada de patata'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Claras de huevo', 150, 'ml' FROM meal_option WHERE name = 'Ensalada de patata'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Patata', 300, 'g' FROM meal_option WHERE name = 'Ensalada de patata'"
        );

        // Opción 7: Ensalada de pasta ~490 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 490.00 WHERE name = 'Ensalada de pasta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Ensalada al gusto', 100, 'g' FROM meal_option WHERE name = 'Ensalada de pasta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tomate cherry', 3, 'unit' FROM meal_option WHERE name = 'Ensalada de pasta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Pasta blanca cocida', 75, 'g' FROM meal_option WHERE name = 'Ensalada de pasta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Frutos secos', 15, 'g' FROM meal_option WHERE name = 'Ensalada de pasta'"
        );

        // =========================================================================
        // MERIENDA
        // =========================================================================

        // Yogur con fruta ~210 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 210.00 WHERE name = 'Yogur con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Fruta (macedonia / fruta natural)', 100, 'g' FROM meal_option WHERE name = 'Yogur con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Yogur proteico', 125, 'g' FROM meal_option WHERE name = 'Yogur con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Frutos secos', 15, 'g' FROM meal_option WHERE name = 'Yogur con fruta'"
        );

        // Queso fresco con fruta ~200 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 200.00 WHERE name = 'Queso fresco con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Frutos secos', 15, 'g' FROM meal_option WHERE name = 'Queso fresco con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Fruta (macedonia)', 100, 'g' FROM meal_option WHERE name = 'Queso fresco con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Queso batido 0%', 125, 'g' FROM meal_option WHERE name = 'Queso fresco con fruta'"
        );

        // Batido con fruta ~300 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 300.00 WHERE name = 'Batido con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Whey protein', 40, 'g' FROM meal_option WHERE name = 'Batido con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Sandía / frutos rojos', 50, 'g' FROM meal_option WHERE name = 'Batido con fruta'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Frutos secos', 20, 'g' FROM meal_option WHERE name = 'Batido con fruta'"
        );

        // Cachas de avena ~380 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 380.00 WHERE name = 'Cachas de avena'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Arándanos', 50, 'g' FROM meal_option WHERE name = 'Cachas de avena'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Avena', 40, 'g' FROM meal_option WHERE name = 'Cachas de avena'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Whey protein', 30, 'g' FROM meal_option WHERE name = 'Cachas de avena'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Agua', 200, 'ml' FROM meal_option WHERE name = 'Cachas de avena'"
        );

        // =========================================================================
        // CENA
        // =========================================================================

        // Tortilla de calabacín ~350 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 350.00 WHERE name = 'Tortilla de calabacín'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tomate natural / cebolla / pimiento', 50, 'g' FROM meal_option WHERE name = 'Tortilla de calabacín'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Huevo entero', 1, 'unit' FROM meal_option WHERE name = 'Tortilla de calabacín'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Claras de huevo', 200, 'ml' FROM meal_option WHERE name = 'Tortilla de calabacín'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Ensalada (ración)', 1, 'ración' FROM meal_option WHERE name = 'Tortilla de calabacín'"
        );

        // Verduras con pollo ~380 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 380.00 WHERE name = 'Verduras con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Verduras (libre)', 200, 'g' FROM meal_option WHERE name = 'Verduras con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Pechuga de pollo', 150, 'g' FROM meal_option WHERE name = 'Verduras con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tomate', 50, 'g' FROM meal_option WHERE name = 'Verduras con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Queso light / sin lactosa', 30, 'g' FROM meal_option WHERE name = 'Verduras con pollo'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Aceite de oliva', 5, 'g' FROM meal_option WHERE name = 'Verduras con pollo'"
        );

        // Ensalada con atún ~300 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 300.00 WHERE name = 'Ensalada con atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Ensalada al gusto (hoja verde)', 150, 'g' FROM meal_option WHERE name = 'Ensalada con atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Atún al natural', 1.5, 'lata' FROM meal_option WHERE name = 'Ensalada con atún'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Aceite de oliva', 5, 'g' FROM meal_option WHERE name = 'Ensalada con atún'"
        );

        // Tortas de arroz con salmón ~310 kcal
        $this->addSql(
            "UPDATE meal_option SET estimated_calories = 310.00 WHERE name = 'Tortas de arroz con salmón'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Salmón ahumado', 70, 'g' FROM meal_option WHERE name = 'Tortas de arroz con salmón'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Aguacate', 30, 'g' FROM meal_option WHERE name = 'Tortas de arroz con salmón'"
        );
        $this->addSql(
            "INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, 'Tortas de arroz', 3, 'unit' FROM meal_option WHERE name = 'Tortas de arroz con salmón'"
        );
    }

    public function down(Schema $schema): void
    {
        // Remove ingredients for all February options
        $options = [
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
        foreach ($options as $name) {
            $this->addSql(
                "DELETE i FROM ingredient i INNER JOIN meal_option mo ON i.meal_option_id = mo.id WHERE mo.name = ?",
                [$name]
            );
            $this->addSql(
                "UPDATE meal_option SET estimated_calories = NULL WHERE name = ?",
                [$name]
            );
        }
    }
}
