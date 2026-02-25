<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224230404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE caloric_goal (id INT AUTO_INCREMENT NOT NULL, daily_calories INT NOT NULL, label VARCHAR(100) DEFAULT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_E4A215E3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE weekly_report (id INT AUTO_INCREMENT NOT NULL, week_start DATE NOT NULL, week_end DATE NOT NULL, target_calories INT NOT NULL, average_calories INT NOT NULL, total_calories INT NOT NULL, days_tracked INT NOT NULL, analysis JSON NOT NULL, summary LONGTEXT NOT NULL, generated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_D9BE55C9A76ED395 (user_id), UNIQUE INDEX uq_user_week (user_id, week_start), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE caloric_goal ADD CONSTRAINT FK_E4A215E3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE weekly_report ADD CONSTRAINT FK_D9BE55C9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caloric_goal DROP FOREIGN KEY FK_E4A215E3A76ED395');
        $this->addSql('ALTER TABLE weekly_report DROP FOREIGN KEY FK_D9BE55C9A76ED395');
        $this->addSql('DROP TABLE caloric_goal');
        $this->addSql('DROP TABLE weekly_report');
    }
}
