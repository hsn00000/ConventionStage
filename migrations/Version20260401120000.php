<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stage campaigns with multiple periods and link contracts to campaigns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stage_campaign (id INT AUTO_INCREMENT NOT NULL, level_id INT NOT NULL, name VARCHAR(150) NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_36618A7D5FB14BA7 (level_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stage_campaign_period (id INT AUTO_INCREMENT NOT NULL, campaign_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, INDEX IDX_C296636F11F5A1B9 (campaign_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contract ADD stage_campaign_id INT DEFAULT NULL, ADD INDEX IDX_947A8B33490384B5 (stage_campaign_id)');
        $this->addSql('ALTER TABLE stage_campaign ADD CONSTRAINT FK_36618A7D5FB14BA7 FOREIGN KEY (level_id) REFERENCES level (id)');
        $this->addSql('ALTER TABLE stage_campaign_period ADD CONSTRAINT FK_C296636F11F5A1B9 FOREIGN KEY (campaign_id) REFERENCES stage_campaign (id)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_947A8B33490384B5 FOREIGN KEY (stage_campaign_id) REFERENCES stage_campaign (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_947A8B33490384B5');
        $this->addSql('ALTER TABLE stage_campaign_period DROP FOREIGN KEY FK_C296636F11F5A1B9');
        $this->addSql('ALTER TABLE stage_campaign DROP FOREIGN KEY FK_36618A7D5FB14BA7');
        $this->addSql('DROP TABLE stage_campaign_period');
        $this->addSql('DROP TABLE stage_campaign');
        $this->addSql('DROP INDEX IDX_947A8B33490384B5 ON contract');
        $this->addSql('ALTER TABLE contract DROP stage_campaign_id');
    }
}
