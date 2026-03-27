<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327140500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add professor rejection reason on contract';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract ADD professor_rejection_reason LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract DROP professor_rejection_reason');
    }
}
