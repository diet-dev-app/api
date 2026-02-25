<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed Plan Nutricional Marzo 2026:
 *  - Inserts new meal_option rows linked to existing meal_time records.
 *  - Inserts ingredient rows for each option with quantity and unit.
 *  - Sets estimated_calories based on standard macro values
 *    (protein/carbs = 4 kcal/g, fat = 9 kcal/g).
 *
 * Meal times expected (from previous migrations):
 *   desayuno, comida, cena
 *
 * March plan structure:
 *   DESAYUNO: 4 options
 *   COMIDA:   6 options
 *   CENA:     5 options
 */
final class Version20260225110000_seed_march_meal_options extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed March 2026 nutritional plan: new meal options with ingredients and calories.';
    }

    // ------------------------------------------------------------------
    // Helper: insert a meal_option and return its name so we can hang
    // ingredients off it.  We use the name as a stable reference key.
    // ------------------------------------------------------------------
    private function insertOption(string $name, string $description, string $mealTimeName, float $calories): void
    {
        $this->addSql(
            'INSERT INTO meal_option (name, description, estimated_calories, meal_time_id)
             VALUES (?, ?, ?, (SELECT id FROM meal_time WHERE name = ?))',
            [$name, $description, $calories, $mealTimeName]
        );
    }

    private function insertIngredient(string $optionName, string $ingredientName, float $quantity, string $unit): void
    {
        $this->addSql(
            'INSERT INTO ingredient (meal_option_id, name, quantity, unit)
             SELECT id, ?, ?, ? FROM meal_option WHERE name = ?',
            [$ingredientName, $quantity, $unit, $optionName]
        );
    }

    public function up(Schema $schema): void
    {
        // =====================================================================
        // DESAYUNO
        // =====================================================================

        // --- Opción 1: Maxi wrap de jamón ~490 kcal ---
        // Maxi wrap (≈50g tortita integral ≈120kcal) + 100g jamón cocido ≈110kcal
        // + 2 lonchas queso light ≈80kcal + 1 cs hummus (≈30g) ≈75kcal
        // + plátano/manzana/naranja ≈80kcal  → ~465 kcal ≈ 490
        $this->insertOption(
            'Maxi wrap de jamón con hummus',
            'Maxi wrap relleno de jamón cocido extra, queso light, hummus y fruta (plátano / manzana / naranja)',
            'desayuno',
            490.00
        );
        $this->insertIngredient('Maxi wrap de jamón con hummus', 'Maxi wrap (tortilla integral grande)', 1, 'unit');
        $this->insertIngredient('Maxi wrap de jamón con hummus', 'Jamón cocido extra', 100, 'g');
        $this->insertIngredient('Maxi wrap de jamón con hummus', 'Queso light en lonchas', 2, 'loncha');
        $this->insertIngredient('Maxi wrap de jamón con hummus', 'Hummus', 30, 'g');
        $this->insertIngredient('Maxi wrap de jamón con hummus', 'Plátano / manzana / naranja (elegir uno)', 1, 'unit');

        // --- Opción 2: Bocadillo de jamón serrano ~520 kcal ---
        // Pan integral 100g ≈230kcal + jamón serrano 80g ≈190kcal
        // + 1 loncha queso light ≈40kcal + tomate libre + fruta ≈80kcal → ~540
        $this->insertOption(
            'Bocadillo de jamón serrano integral',
            'Pan integral con jamón serrano, queso light, tomate y fruta',
            'desayuno',
            540.00
        );
        $this->insertIngredient('Bocadillo de jamón serrano integral', 'Pan integral', 100, 'g');
        $this->insertIngredient('Bocadillo de jamón serrano integral', 'Jamón serrano', 80, 'g');
        $this->insertIngredient('Bocadillo de jamón serrano integral', 'Queso light en loncha', 1, 'loncha');
        $this->insertIngredient('Bocadillo de jamón serrano integral', 'Tomate al gusto', 80, 'g');
        $this->insertIngredient('Bocadillo de jamón serrano integral', 'Plátano / manzana / naranja (elegir uno)', 1, 'unit');

        // --- Opción 3: Tortitas de avena ~510 kcal ---
        // Harina avena 70g ≈270kcal + 1 huevo ≈75kcal + 150ml claras ≈50kcal
        // + 1 plátano ≈90kcal + 15g crema cacahuete ≈90kcal → ~575 kcal → usamos 510 ajustado
        $this->insertOption(
            'Tortitas de avena con plátano y cacahuete',
            'Tortitas de harina de avena con huevo y claras, decoradas con plátano y crema de cacahuete',
            'desayuno',
            570.00
        );
        $this->insertIngredient('Tortitas de avena con plátano y cacahuete', 'Harina de avena (sabor a elegir)', 70, 'g');
        $this->insertIngredient('Tortitas de avena con plátano y cacahuete', 'Huevo entero', 1, 'unit');
        $this->insertIngredient('Tortitas de avena con plátano y cacahuete', 'Claras de huevo', 150, 'ml');
        $this->insertIngredient('Tortitas de avena con plátano y cacahuete', 'Levadura (al gusto)', 2, 'g');
        $this->insertIngredient('Tortitas de avena con plátano y cacahuete', 'Plátano', 1, 'unit');
        $this->insertIngredient('Tortitas de avena con plátano y cacahuete', 'Crema de cacahuete', 15, 'g');

        // --- Opción 4: Yogur proteico con mango y cereales ~480 kcal ---
        // 250g yogur proteico ≈175kcal + 1/2 mango ≈60kcal + 40g corn flakes ≈150kcal
        // + 25g nueces ≈165kcal → ~550 kcal, redondeamos a 540
        $this->insertOption(
            'Bol de yogur proteico con mango y cereales',
            'Yogur proteico con mango o manzana grande, copos de maíz sin azúcar y nueces',
            'desayuno',
            540.00
        );
        $this->insertIngredient('Bol de yogur proteico con mango y cereales', 'Yogur proteico', 250, 'g');
        $this->insertIngredient('Bol de yogur proteico con mango y cereales', 'Mango (1/2) o manzana grande', 1, 'unit');
        $this->insertIngredient('Bol de yogur proteico con mango y cereales', 'Copos de maíz sin azúcar (corn flakes)', 40, 'g');
        $this->insertIngredient('Bol de yogur proteico con mango y cereales', 'Nueces', 25, 'g');

        // =====================================================================
        // COMIDA
        // =====================================================================

        // --- Opción 1: Ensalada de pasta con pollo y atún ~600 kcal ---
        // Hoja verde libre + pasta 80g seco ≈280kcal + pechuga 160g ≈190kcal
        // + 1 huevo ≈75kcal + hortalizas libre + salsa yogur: 1 yogur natural ~60kcal
        // + 1 cs aceite oliva ≈45kcal + fruta ≈70kcal → ~720 kcal; ≈650 conservador
        $this->insertOption(
            'Ensalada de pasta con pollo o atún',
            'Base hoja verde, pasta, pollo o atún, huevo cocido, hortalizas y salsa yogur casera. Con fruta.',
            'comida',
            650.00
        );
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Hoja verde (al gusto)', 100, 'g');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Pasta (pesada en seco)', 80, 'g');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Tiras de pollo para ensalada o atún al natural', 160, 'g');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Huevo cocido', 1, 'unit');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Hortalizas al gusto', 80, 'g');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Yogur natural (para salsa)', 1, 'unit');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Aceite de oliva', 10, 'ml');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Zumo de limón (1/2)', 15, 'ml');
        $this->insertIngredient('Ensalada de pasta con pollo o atún', 'Fruta (manzana / pera / plátano pequeño)', 1, 'unit');

        // --- Opción 2: Arroz con pollo ~630 kcal ---
        // Arroz 80g seco ≈280kcal + pechuga 160g ≈190kcal + sofrito (10ml AOVE ≈90kcal)
        // + fruta ≈80kcal → ~640
        $this->insertOption(
            'Guiso de arroz con pollo',
            'Arroz con pechuga de pollo en dados, sofrito de pimiento, cebolla y tomate con aceite de oliva',
            'comida',
            640.00
        );
        $this->insertIngredient('Guiso de arroz con pollo', 'Arroz (pesado en seco)', 80, 'g');
        $this->insertIngredient('Guiso de arroz con pollo', 'Pechuga de pollo en dados', 160, 'g');
        $this->insertIngredient('Guiso de arroz con pollo', 'Pimiento', 50, 'g');
        $this->insertIngredient('Guiso de arroz con pollo', 'Cebolla', 50, 'g');
        $this->insertIngredient('Guiso de arroz con pollo', 'Tomate natural', 80, 'g');
        $this->insertIngredient('Guiso de arroz con pollo', 'Aceite de oliva', 10, 'ml');
        $this->insertIngredient('Guiso de arroz con pollo', 'Agua o caldo', 300, 'ml');
        $this->insertIngredient('Guiso de arroz con pollo', 'Fruta (manzana / pera / plátano pequeño)', 1, 'unit');

        // --- Opción 3: Ensalada de garbanzos con atún ~610 kcal ---
        // Garbanzos 200g cocidos ≈240kcal + 2 latas atún ≈200kcal + hortalizas libre
        // + 10ml AOVE ≈90kcal + 60g pan integral ≈150kcal + fruta ≈80kcal → ~760
        // Con 1 lata y sin pan sería ≈530; usamos valores del plan → ~610 conservador
        $this->insertOption(
            'Ensalada de garbanzos con atún',
            'Garbanzos cocidos, atún al natural, hortalizas, aliñada con aceite de oliva y acompañada de pan integral',
            'comida',
            660.00
        );
        $this->insertIngredient('Ensalada de garbanzos con atún', 'Garbanzos cocidos', 200, 'g');
        $this->insertIngredient('Ensalada de garbanzos con atún', 'Atún al natural', 2, 'lata');
        $this->insertIngredient('Ensalada de garbanzos con atún', 'Hortalizas al gusto (tomate, cebolla, pimiento, zanahoria)', 150, 'g');
        $this->insertIngredient('Ensalada de garbanzos con atún', 'Aceite de oliva', 10, 'ml');
        $this->insertIngredient('Ensalada de garbanzos con atún', 'Pan integral', 60, 'g');
        $this->insertIngredient('Ensalada de garbanzos con atún', 'Fruta (manzana / pera / plátano pequeño)', 1, 'unit');

        // --- Opción 4: Guiso de lentejas con pollo ~680 kcal ---
        // Lentejas cocidas 200g ≈230kcal + muslo pollo 100g ≈140kcal + patata ≈80kcal
        // + sofrito 15ml AOVE ≈135kcal + fruta ≈80kcal → ~665
        $this->insertOption(
            'Guiso de lentejas con pollo',
            'Lentejas cocidas con muslos de pollo deshuesados sin piel, patata y sofrito de verduras',
            'comida',
            665.00
        );
        $this->insertIngredient('Guiso de lentejas con pollo', 'Lentejas cocidas', 200, 'g');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Muslos de pollo deshuesados sin piel', 100, 'g');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Patata pequeña', 1, 'unit');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Pimiento', 40, 'g');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Cebolla', 40, 'g');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Tomate natural', 80, 'g');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Aceite de oliva', 15, 'ml');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Agua o caldo', 300, 'ml');
        $this->insertIngredient('Guiso de lentejas con pollo', 'Fruta (manzana / pera / plátano pequeño)', 1, 'unit');

        // --- Opción 5: Pisto con patata y merluza ~560 kcal ---
        // Pisto 200g ≈100kcal + patatas 250g asadas + 10ml AOVE ≈325kcal
        // + merluza 200g ≈150kcal + fruta ≈80kcal → ~655; conservador 560
        $this->insertOption(
            'Pisto con patatas asadas y merluza',
            'Pisto de verduras, patatas asadas o cocidas con aceite de oliva y filetes de merluza',
            'comida',
            620.00
        );
        $this->insertIngredient('Pisto con patatas asadas y merluza', 'Pisto de verduras', 200, 'g');
        $this->insertIngredient('Pisto con patatas asadas y merluza', 'Patatas asadas o cocidas', 250, 'g');
        $this->insertIngredient('Pisto con patatas asadas y merluza', 'Aceite de oliva', 10, 'ml');
        $this->insertIngredient('Pisto con patatas asadas y merluza', 'Filetes de merluza sin piel (u otro pescado blanco)', 200, 'g');
        $this->insertIngredient('Pisto con patatas asadas y merluza', 'Fruta (manzana / pera / plátano pequeño)', 1, 'unit');

        // --- Opción 6: Poke de salmón ~700 kcal ---
        // Arroz 80g seco ≈280kcal + salmón individual ≈200kcal + hortalizas libre
        // + 1/2 mango ≈60kcal + 1/4 aguacate ≈60kcal + salsa mayo light+soja ≈50kcal → ~660
        $this->insertOption(
            'Poke de salmón',
            'Arroz con salmón marinado en soja y lima, tomate cherry, pepino, zanahoria, mango, aguacate y salsa de mayonesa light con soja',
            'comida',
            660.00
        );
        $this->insertIngredient('Poke de salmón', 'Arroz (pesado en seco)', 80, 'g');
        $this->insertIngredient('Poke de salmón', 'Lomo de salmón individual', 150, 'g');
        $this->insertIngredient('Poke de salmón', 'Salsa de soja', 15, 'ml');
        $this->insertIngredient('Poke de salmón', 'Zumo de lima', 15, 'ml');
        $this->insertIngredient('Poke de salmón', 'Tomate cherry', 6, 'unit');
        $this->insertIngredient('Poke de salmón', 'Pepino', 60, 'g');
        $this->insertIngredient('Poke de salmón', 'Zanahoria rallada', 40, 'g');
        $this->insertIngredient('Poke de salmón', 'Mango (1/2)', 80, 'g');
        $this->insertIngredient('Poke de salmón', 'Aguacate (1/4)', 40, 'g');
        $this->insertIngredient('Poke de salmón', 'Mayonesa light', 10, 'g');

        // =====================================================================
        // CENA
        // =====================================================================

        // --- Opción 1: Salmón con arroz y espárragos ~530 kcal ---
        // Salmón 125g ≈250kcal + arroz 60g seco ≈210kcal ó patata 250g ≈195kcal
        // + espárragos libre + yogur proteico ≈80kcal → ~540
        $this->insertOption(
            'Salmón con arroz o patata y espárragos',
            'Salmón a la plancha con limón, arroz o patata/boniato, espárragos o alcachofas y yogur proteico',
            'cena',
            530.00
        );
        $this->insertIngredient('Salmón con arroz o patata y espárragos', 'Salmón', 125, 'g');
        $this->insertIngredient('Salmón con arroz o patata y espárragos', 'Zumo de limón', 15, 'ml');
        $this->insertIngredient('Salmón con arroz o patata y espárragos', 'Arroz (pesado en seco) o patata/boniato cocido', 60, 'g');
        $this->insertIngredient('Salmón con arroz o patata y espárragos', 'Espárragos verdes o alcachofas', 150, 'g');
        $this->insertIngredient('Salmón con arroz o patata y espárragos', 'Yogur proteico', 125, 'g');

        // --- Opción 2: Pita con guacamole y atún ~490 kcal ---
        // Canónigos+tomate+pepino libre + 2 pan pita ≈170kcal + 60g guacamole ≈90kcal
        // + 1.5 latas atún ≈180kcal + 5ml AOVE ≈45kcal + yogur proteico ≈80kcal → ~565
        $this->insertOption(
            'Pita con guacamole y atún',
            'Ensalada de canónigos aliñada, pitas rellenas de guacamole y atún, yogur proteico',
            'cena',
            530.00
        );
        $this->insertIngredient('Pita con guacamole y atún', 'Canónigos', 60, 'g');
        $this->insertIngredient('Pita con guacamole y atún', 'Tomate', 80, 'g');
        $this->insertIngredient('Pita con guacamole y atún', 'Pepino', 60, 'g');
        $this->insertIngredient('Pita con guacamole y atún', 'Aceite de oliva virgen extra', 5, 'ml');
        $this->insertIngredient('Pita con guacamole y atún', 'Pan pita', 2, 'unit');
        $this->insertIngredient('Pita con guacamole y atún', 'Guacamole', 60, 'g');
        $this->insertIngredient('Pita con guacamole y atún', 'Atún al natural', 1.5, 'lata');
        $this->insertIngredient('Pita con guacamole y atún', 'Yogur proteico', 125, 'g');

        // --- Opción 3: Crema de verduras con lomo y queso ~580 kcal ---
        // Crema 350ml ≈100kcal + lomo cerdo 140g ≈240kcal + 1 loncha queso havarti light ≈50kcal
        // + pan integral 120g ≈270kcal + yogur proteico ≈80kcal → ~740; sin pan ~470
        $this->insertOption(
            'Crema de verduras con lomo de cerdo',
            'Crema de verduras, lomo de cerdo a la plancha, queso havarti light, pan integral y yogur proteico',
            'cena',
            580.00
        );
        $this->insertIngredient('Crema de verduras con lomo de cerdo', 'Crema de verduras', 350, 'ml');
        $this->insertIngredient('Crema de verduras con lomo de cerdo', 'Lomo de cerdo (corte limpio de grasa)', 140, 'g');
        $this->insertIngredient('Crema de verduras con lomo de cerdo', 'Queso havarti light', 1, 'loncha');
        $this->insertIngredient('Crema de verduras con lomo de cerdo', 'Pan integral', 120, 'g');
        $this->insertIngredient('Crema de verduras con lomo de cerdo', 'Yogur proteico', 125, 'g');

        // --- Opción 4: Hamburguesa de ternera integral ~590 kcal ---
        // Hamburguesa ternera 90% ≈200kcal + pan integral ≈180kcal + queso havarti light ≈50kcal
        // + tomate ½ ≈15kcal + ensalada tomate-pepino + 10ml AOVE ≈90kcal
        // + yogur proteico ≈80kcal → ~615
        $this->insertOption(
            'Hamburguesa de ternera integral',
            'Hamburguesa de ternera (mínimo 90% carne) en pan integral con queso havarti light, tomate y ensalada. Yogur proteico.',
            'cena',
            590.00
        );
        $this->insertIngredient('Hamburguesa de ternera integral', 'Hamburguesa de ternera (mínimo 90% carne)', 150, 'g');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Pan de hamburguesa integral', 1, 'unit');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Queso havarti light', 1, 'loncha');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Tomate (1/4)', 40, 'g');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Salsa zero (al gusto)', 10, 'g');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Tomate para ensalada', 80, 'g');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Pepino', 60, 'g');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Aceite de oliva', 10, 'ml');
        $this->insertIngredient('Hamburguesa de ternera integral', 'Yogur proteico', 125, 'g');

        // --- Opción 5: Pizza extrafina de atún o pollo ~530 kcal ---
        // Base extrafina ≈200kcal + tomate triturado ≈30kcal + 2 latas atún ≈200kcal
        // + 40g mozzarella ≈120kcal + verduras libre + salsa bbq 0% libre
        // + yogur proteico ≈80kcal → ~630; conservador con 1 lata → ~480
        $this->insertOption(
            'Pizza extrafina de atún o pollo',
            'Base de pizza extrafina con tomate, atún o tiras de pollo, mozzarella y salsa barbacoa 0%. Yogur proteico.',
            'cena',
            560.00
        );
        $this->insertIngredient('Pizza extrafina de atún o pollo', 'Base de pizza extrafina (Mercadona)', 1, 'unit');
        $this->insertIngredient('Pizza extrafina de atún o pollo', 'Tomate triturado', 80, 'g');
        $this->insertIngredient('Pizza extrafina de atún o pollo', 'Atún al natural o tiras de pechuga de pollo', 160, 'g');
        $this->insertIngredient('Pizza extrafina de atún o pollo', 'Mozzarella rallada', 40, 'g');
        $this->insertIngredient('Pizza extrafina de atún o pollo', 'Cebolla o champiñones (opcional)', 50, 'g');
        $this->insertIngredient('Pizza extrafina de atún o pollo', 'Salsa barbacoa 0%', 15, 'g');
        $this->insertIngredient('Pizza extrafina de atún o pollo', 'Yogur proteico', 125, 'g');
    }

    public function down(Schema $schema): void
    {
        $options = [
            // desayuno
            'Maxi wrap de jamón con hummus',
            'Bocadillo de jamón serrano integral',
            'Tortitas de avena con plátano y cacahuete',
            'Bol de yogur proteico con mango y cereales',
            // comida
            'Ensalada de pasta con pollo o atún',
            'Guiso de arroz con pollo',
            'Ensalada de garbanzos con atún',
            'Guiso de lentejas con pollo',
            'Pisto con patatas asadas y merluza',
            'Poke de salmón',
            // cena
            'Salmón con arroz o patata y espárragos',
            'Pita con guacamole y atún',
            'Crema de verduras con lomo de cerdo',
            'Hamburguesa de ternera integral',
            'Pizza extrafina de atún o pollo',
        ];

        foreach ($options as $name) {
            $this->addSql(
                'DELETE i FROM ingredient i INNER JOIN meal_option mo ON i.meal_option_id = mo.id WHERE mo.name = ?',
                [$name]
            );
            $this->addSql('DELETE FROM meal_option WHERE name = ?', [$name]);
        }
    }
}
