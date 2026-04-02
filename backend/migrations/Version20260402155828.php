<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402155828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create group, participant, bill and bill_share tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bill (id UUID NOT NULL, description VARCHAR(255) NOT NULL, amount_cents INT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, split_type VARCHAR(255) NOT NULL, paid_by_id UUID NOT NULL, group_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_7A2119E37F9BC654 ON bill (paid_by_id)');
        $this->addSql('CREATE INDEX IDX_7A2119E3FE54D947 ON bill (group_id)');
        $this->addSql('CREATE TABLE bill_share (id UUID NOT NULL, amount_cents INT NOT NULL, bill_id UUID NOT NULL, participant_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D9F6C8C41A8C12F5 ON bill_share (bill_id)');
        $this->addSql('CREATE INDEX IDX_D9F6C8C49D1C3019 ON bill_share (participant_id)');
        $this->addSql('CREATE TABLE "group" (id UUID NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE participant (id UUID NOT NULL, name VARCHAR(255) NOT NULL, group_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D79F6B11FE54D947 ON participant (group_id)');
        $this->addSql('ALTER TABLE bill ADD CONSTRAINT FK_7A2119E37F9BC654 FOREIGN KEY (paid_by_id) REFERENCES participant (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE bill ADD CONSTRAINT FK_7A2119E3FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE bill_share ADD CONSTRAINT FK_D9F6C8C41A8C12F5 FOREIGN KEY (bill_id) REFERENCES bill (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE bill_share ADD CONSTRAINT FK_D9F6C8C49D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bill DROP CONSTRAINT FK_7A2119E37F9BC654');
        $this->addSql('ALTER TABLE bill DROP CONSTRAINT FK_7A2119E3FE54D947');
        $this->addSql('ALTER TABLE bill_share DROP CONSTRAINT FK_D9F6C8C41A8C12F5');
        $this->addSql('ALTER TABLE bill_share DROP CONSTRAINT FK_D9F6C8C49D1C3019');
        $this->addSql('ALTER TABLE participant DROP CONSTRAINT FK_D79F6B11FE54D947');
        $this->addSql('DROP TABLE bill');
        $this->addSql('DROP TABLE bill_share');
        $this->addSql('DROP TABLE "group"');
        $this->addSql('DROP TABLE participant');
    }
}
