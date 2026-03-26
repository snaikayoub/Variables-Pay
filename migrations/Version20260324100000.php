<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make prime_fonction.taux_monetaire_fonction optional (auto-derived)';
    }

    public function up(Schema $schema): void
    {
        // Allow creating/editing PrimeFonction without manual input of the monetary rate.
        $this->addSql('ALTER TABLE prime_fonction CHANGE taux_monetaire_fonction taux_monetaire_fonction DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prime_fonction CHANGE taux_monetaire_fonction taux_monetaire_fonction DOUBLE PRECISION NOT NULL');
    }
}
