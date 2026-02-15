<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215191639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE meal_time (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, label VARCHAR(100) DEFAULT NULL, UNIQUE INDEX UNIQ_754A4BB15E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE meal_option ADD meal_time_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE meal_option ADD CONSTRAINT FK_358CBDB927596F22 FOREIGN KEY (meal_time_id) REFERENCES meal_time (id)');
        $this->addSql('CREATE INDEX IDX_358CBDB927596F22 ON meal_option (meal_time_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE meal_time');
        $this->addSql('ALTER TABLE meal_option DROP FOREIGN KEY FK_358CBDB927596F22');
        $this->addSql('DROP INDEX IDX_358CBDB927596F22 ON meal_option');
        $this->addSql('ALTER TABLE meal_option DROP meal_time_id');
    }
}
