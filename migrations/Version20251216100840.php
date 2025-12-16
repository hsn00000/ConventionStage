<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216100840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE level ADD main_professor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE level ADD CONSTRAINT FK_9AEACC136495316C FOREIGN KEY (main_professor_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9AEACC136495316C ON level (main_professor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE level DROP FOREIGN KEY FK_9AEACC136495316C');
        $this->addSql('DROP INDEX IDX_9AEACC136495316C ON level');
        $this->addSql('ALTER TABLE level DROP main_professor_id');
    }
}
