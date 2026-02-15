<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215194000_truncate_meal_option extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Truncate meal_option table to allow foreign key migration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('TRUNCATE TABLE meal_option');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        // No down migration for truncate
    }
}
