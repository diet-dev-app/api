<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215215905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE meal_meal_option (meal_id INT NOT NULL, meal_option_id INT NOT NULL, INDEX IDX_3876BB31639666D6 (meal_id), INDEX IDX_3876BB3119AB1F64 (meal_option_id), PRIMARY KEY (meal_id, meal_option_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE meal_meal_option ADD CONSTRAINT FK_3876BB31639666D6 FOREIGN KEY (meal_id) REFERENCES meal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meal_meal_option ADD CONSTRAINT FK_3876BB3119AB1F64 FOREIGN KEY (meal_option_id) REFERENCES meal_option (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meal_meal_option DROP FOREIGN KEY FK_3876BB31639666D6');
        $this->addSql('ALTER TABLE meal_meal_option DROP FOREIGN KEY FK_3876BB3119AB1F64');
        $this->addSql('DROP TABLE meal_meal_option');
    }
}
