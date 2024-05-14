<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240514003506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice CHANGE stripe_id stripe_id VARCHAR(255) DEFAULT NULL, CHANGE amount_paid amount_paid INT DEFAULT NULL, CHANGE number number VARCHAR(255) DEFAULT NULL, CHANGE hosted_invoice_url hosted_invoice_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE plan ADD description LONGTEXT DEFAULT NULL, CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE slug slug VARCHAR(255) DEFAULT NULL, CHANGE stripe_id stripe_id VARCHAR(255) DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE payment_link payment_link VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice CHANGE stripe_id stripe_id VARCHAR(255) NOT NULL, CHANGE amount_paid amount_paid INT NOT NULL, CHANGE number number VARCHAR(255) NOT NULL, CHANGE hosted_invoice_url hosted_invoice_url VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE plan DROP description, CHANGE name name VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL, CHANGE stripe_id stripe_id VARCHAR(255) NOT NULL, CHANGE price price INT NOT NULL, CHANGE payment_link payment_link VARCHAR(255) NOT NULL');
    }
}
