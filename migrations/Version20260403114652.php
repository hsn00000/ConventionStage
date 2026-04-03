<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403114652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session_date DROP FOREIGN KEY FK_6CEF3750613FECDF');
        $this->addSql('DROP TABLE session_date');
        $this->addSql('DROP TABLE session');
        $this->addSql('ALTER TABLE contract ADD ddf_rejection_reason LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contract RENAME INDEX idx_947a8b33490384b5 TO IDX_E98F285927CECA9E');
        $this->addSql('ALTER TABLE stage_campaign RENAME INDEX idx_36618a7d5fb14ba7 TO IDX_17F2D6B05FB14BA7');
        $this->addSql('ALTER TABLE stage_campaign_period RENAME INDEX idx_c296636f11f5a1b9 TO IDX_27A325C4F639F774');
        $this->addSql('ALTER TABLE user DROP personal_email');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE session_date (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, INDEX IDX_6CEF3750613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE session_date ADD CONSTRAINT FK_6CEF3750613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE stage_campaign RENAME INDEX idx_17f2d6b05fb14ba7 TO IDX_36618A7D5FB14BA7');
        $this->addSql('ALTER TABLE contract DROP ddf_rejection_reason');
        $this->addSql('ALTER TABLE contract RENAME INDEX idx_e98f285927ceca9e TO IDX_947A8B33490384B5');
        $this->addSql('ALTER TABLE stage_campaign_period RENAME INDEX idx_27a325c4f639f774 TO IDX_C296636F11F5A1B9');
        $this->addSql('ALTER TABLE user ADD personal_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
    }
}
