<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217080835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD prof_referent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649F9D4A789 FOREIGN KEY (prof_referent_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649F9D4A789 ON user (prof_referent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649F9D4A789');
        $this->addSql('DROP INDEX IDX_8D93D649F9D4A789 ON user');
        $this->addSql('ALTER TABLE user DROP prof_referent_id');
    }
}
