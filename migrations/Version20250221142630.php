<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250221142630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answers RENAME INDEX idx_50d0c6064faf8f53 TO IDX_50D0C6061E27F6BF');
        $this->addSql('ALTER TABLE episodes ADD transcript LONGTEXT DEFAULT NULL, ADD video_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE questions RENAME INDEX idx_8adc54d58337e7d7 TO IDX_8ADC54D5853CD175');
        $this->addSql('ALTER TABLE quizzes RENAME INDEX idx_94dc9fb5444e6803 TO IDX_94DC9FB5362B62A0');
        $this->addSql('ALTER TABLE user_answer_choices RENAME INDEX idx_260b861164b20b06 TO IDX_260B8611AAD3C5E3');
        $this->addSql('ALTER TABLE user_answer_choices RENAME INDEX idx_260b8611e47e7704 TO IDX_260B8611AA334807');
        $this->addSql('ALTER TABLE user_answers RENAME INDEX idx_8ddd80c9d86650f TO IDX_8DDD80CA76ED395');
        $this->addSql('ALTER TABLE user_answers RENAME INDEX idx_8ddd80c4faf8f53 TO IDX_8DDD80C1E27F6BF');
        $this->addSql('ALTER TABLE user_answers RENAME INDEX idx_8ddd80ce47e7704 TO IDX_8DDD80CAA334807');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answers RENAME INDEX idx_50d0c6061e27f6bf TO IDX_50D0C6064FAF8F53');
        $this->addSql('ALTER TABLE quizzes RENAME INDEX idx_94dc9fb5362b62a0 TO IDX_94DC9FB5444E6803');
        $this->addSql('ALTER TABLE user_answers RENAME INDEX idx_8ddd80ca76ed395 TO IDX_8DDD80C9D86650F');
        $this->addSql('ALTER TABLE user_answers RENAME INDEX idx_8ddd80c1e27f6bf TO IDX_8DDD80C4FAF8F53');
        $this->addSql('ALTER TABLE user_answers RENAME INDEX idx_8ddd80caa334807 TO IDX_8DDD80CE47E7704');
        $this->addSql('ALTER TABLE user_answer_choices RENAME INDEX idx_260b8611aad3c5e3 TO IDX_260B861164B20B06');
        $this->addSql('ALTER TABLE user_answer_choices RENAME INDEX idx_260b8611aa334807 TO IDX_260B8611E47E7704');
        $this->addSql('ALTER TABLE episodes DROP transcript, DROP video_url');
        $this->addSql('ALTER TABLE questions RENAME INDEX idx_8adc54d5853cd175 TO IDX_8ADC54D58337E7D7');
    }
}
