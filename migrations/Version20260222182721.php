<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222182721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ingredient (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, quantity NUMERIC(8, 2) NOT NULL, unit VARCHAR(30) NOT NULL, meal_option_id INT NOT NULL, INDEX IDX_6BAF787019AB1F64 (meal_option_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ingredient ADD CONSTRAINT FK_6BAF787019AB1F64 FOREIGN KEY (meal_option_id) REFERENCES meal_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meal_option ADD estimated_calories NUMERIC(8, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingredient DROP FOREIGN KEY FK_6BAF787019AB1F64');
        $this->addSql('DROP TABLE ingredient');
        $this->addSql('ALTER TABLE meal_option DROP estimated_calories');
    }
}
