<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401001500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pris_en_charge flag to voyage_deplacement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE voyage_deplacement ADD pris_en_charge TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE voyage_deplacement DROP pris_en_charge');
    }
}
