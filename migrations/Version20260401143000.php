<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove duplicated contract dates and user-campaign links while keeping DDF schedules by class';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract_date DROP FOREIGN KEY FK_C2EB79122576E0FD');
        $this->addSql('DROP TABLE contract_date');
        $this->addSql('DROP TABLE IF EXISTS session_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contract_date (id INT AUTO_INCREMENT NOT NULL, contract_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, INDEX IDX_C2EB79122576E0FD (contract_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contract_date ADD CONSTRAINT FK_C2EB79122576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id)');
        $this->addSql('CREATE TABLE session_user (campaign_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_BD35043B11F5A1B9 (campaign_id), INDEX IDX_BD35043BA76ED395 (user_id), PRIMARY KEY(campaign_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE session_user ADD CONSTRAINT FK_BD35043B11F5A1B9 FOREIGN KEY (campaign_id) REFERENCES stage_campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session_user ADD CONSTRAINT FK_BD35043BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }
}
