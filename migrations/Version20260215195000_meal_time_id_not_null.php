<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215195000_meal_time_id_not_null extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set meal_time_id in meal_option as NOT NULL after data is populated.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE meal_option MODIFY meal_time_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE meal_option MODIFY meal_time_id INT DEFAULT NULL');
    }
}
