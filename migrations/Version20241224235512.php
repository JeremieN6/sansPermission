<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241224235512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answers (id INT AUTO_INCREMENT NOT NULL, question_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL, is_correct TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_50D0C6064FAF8F53 (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE episodes (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, release_date DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, subscription_id INT DEFAULT NULL, stripe_id VARCHAR(255) DEFAULT NULL, amount_paid INT DEFAULT NULL, number VARCHAR(255) DEFAULT NULL, hosted_invoice_url VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_906517449A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, stripe_id VARCHAR(255) DEFAULT NULL, price INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', payment_link VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE questions (id INT AUTO_INCREMENT NOT NULL, quiz_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8ADC54D58337E7D7 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quizzes (id INT AUTO_INCREMENT NOT NULL, episode_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_94DC9FB5444E6803 (episode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, plan_id INT DEFAULT NULL, user_id INT DEFAULT NULL, stripe_id VARCHAR(255) DEFAULT NULL, current_period_start DATETIME NOT NULL, current_period_end DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_A3C664D3E899029B (plan_id), INDEX IDX_A3C664D3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transcript (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_answer_choices (id INT AUTO_INCREMENT NOT NULL, user_answer_id INT DEFAULT NULL, answer_id INT DEFAULT NULL, is_correct TINYINT(1) DEFAULT NULL, INDEX IDX_260B861164B20B06 (user_answer_id), INDEX IDX_260B8611E47E7704 (answer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_answers (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, question_id INT DEFAULT NULL, answer_id INT DEFAULT NULL, is_correct TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8DDD80C9D86650F (user_id), INDEX IDX_8DDD80C4FAF8F53 (question_id), INDEX IDX_8DDD80CE47E7704 (answer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, nom VARCHAR(100) DEFAULT NULL, prenom VARCHAR(100) DEFAULT NULL, age INT DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, pseudo VARCHAR(100) NOT NULL, is_verified TINYINT(1) NOT NULL, stripe_id VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE answers ADD CONSTRAINT FK_50D0C6064FAF8F53 FOREIGN KEY (question_id) REFERENCES questions (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517449A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE questions ADD CONSTRAINT FK_8ADC54D58337E7D7 FOREIGN KEY (quiz_id) REFERENCES quizzes (id)');
        $this->addSql('ALTER TABLE quizzes ADD CONSTRAINT FK_94DC9FB5444E6803 FOREIGN KEY (episode_id) REFERENCES episodes (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_answer_choices ADD CONSTRAINT FK_260B861164B20B06 FOREIGN KEY (user_answer_id) REFERENCES user_answers (id)');
        $this->addSql('ALTER TABLE user_answer_choices ADD CONSTRAINT FK_260B8611E47E7704 FOREIGN KEY (answer_id) REFERENCES answers (id)');
        $this->addSql('ALTER TABLE user_answers ADD CONSTRAINT FK_8DDD80C9D86650F FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_answers ADD CONSTRAINT FK_8DDD80C4FAF8F53 FOREIGN KEY (question_id) REFERENCES questions (id)');
        $this->addSql('ALTER TABLE user_answers ADD CONSTRAINT FK_8DDD80CE47E7704 FOREIGN KEY (answer_id) REFERENCES answers (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answers DROP FOREIGN KEY FK_50D0C6064FAF8F53');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517449A1887DC');
        $this->addSql('ALTER TABLE questions DROP FOREIGN KEY FK_8ADC54D58337E7D7');
        $this->addSql('ALTER TABLE quizzes DROP FOREIGN KEY FK_94DC9FB5444E6803');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE user_answer_choices DROP FOREIGN KEY FK_260B861164B20B06');
        $this->addSql('ALTER TABLE user_answer_choices DROP FOREIGN KEY FK_260B8611E47E7704');
        $this->addSql('ALTER TABLE user_answers DROP FOREIGN KEY FK_8DDD80C9D86650F');
        $this->addSql('ALTER TABLE user_answers DROP FOREIGN KEY FK_8DDD80C4FAF8F53');
        $this->addSql('ALTER TABLE user_answers DROP FOREIGN KEY FK_8DDD80CE47E7704');
        $this->addSql('DROP TABLE answers');
        $this->addSql('DROP TABLE episodes');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE plan');
        $this->addSql('DROP TABLE questions');
        $this->addSql('DROP TABLE quizzes');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE transcript');
        $this->addSql('DROP TABLE user_answer_choices');
        $this->addSql('DROP TABLE user_answers');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
