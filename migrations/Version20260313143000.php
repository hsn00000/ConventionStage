<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add YouSign identifiers on contract and align workflow status names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract ADD yousign_document_id VARCHAR(255) DEFAULT NULL, ADD yousign_signature_request_id VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE contract SET status = 'collection_sent' WHERE status = 'Brouillon'");
        $this->addSql("UPDATE contract SET status = 'collection_sent' WHERE status = 'En attente entreprise'");
        $this->addSql("UPDATE contract SET status = 'validated_by_student' WHERE status IN ('En attente', 'A valider Prof')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE contract SET status = 'A valider Prof' WHERE status = 'validated_by_student'");
        $this->addSql("UPDATE contract SET status = 'Brouillon' WHERE status = 'collection_sent'");
        $this->addSql('ALTER TABLE contract DROP yousign_document_id, DROP yousign_signature_request_id');
    }
}
