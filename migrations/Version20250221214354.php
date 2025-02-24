<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250221214354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aimodel (id INT AUTO_INCREMENT NOT NULL, model_id VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, training_metrics LONGTEXT DEFAULT NULL, configuration JSON DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE aimodel_episodes (aimodel_id INT NOT NULL, episodes_id INT NOT NULL, INDEX IDX_296A733631385849 (aimodel_id), INDEX IDX_296A7336319135AF (episodes_id), PRIMARY KEY(aimodel_id, episodes_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aimodel_episodes ADD CONSTRAINT FK_296A733631385849 FOREIGN KEY (aimodel_id) REFERENCES aimodel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aimodel_episodes ADD CONSTRAINT FK_296A7336319135AF FOREIGN KEY (episodes_id) REFERENCES episodes (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aimodel_episodes DROP FOREIGN KEY FK_296A733631385849');
        $this->addSql('ALTER TABLE aimodel_episodes DROP FOREIGN KEY FK_296A7336319135AF');
        $this->addSql('DROP TABLE aimodel');
        $this->addSql('DROP TABLE aimodel_episodes');
    }
}
