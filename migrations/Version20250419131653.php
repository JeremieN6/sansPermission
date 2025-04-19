<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250419131653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) DEFAULT NULL, nom VARCHAR(125) DEFAULT NULL, message LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz_atempt (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ended_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', score INT DEFAULT NULL, duration INT DEFAULT NULL, INDEX IDX_A7DC589BA76ED395 (user_id), INDEX IDX_A7DC589B853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quiz_atempt ADD CONSTRAINT FK_A7DC589BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE quiz_atempt ADD CONSTRAINT FK_A7DC589B853CD175 FOREIGN KEY (quiz_id) REFERENCES quizzes (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quiz_atempt DROP FOREIGN KEY FK_A7DC589BA76ED395');
        $this->addSql('ALTER TABLE quiz_atempt DROP FOREIGN KEY FK_A7DC589B853CD175');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE quiz_atempt');
    }
}
