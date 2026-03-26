<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add categorie_fonction entity and link it to employee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categorie_fonction (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, taux_monetaire DOUBLE PRECISION NOT NULL, UNIQUE INDEX UNIQ_9F1719B777153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE employee ADD categorie_fonction_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A14DBFE673 FOREIGN KEY (categorie_fonction_id) REFERENCES categorie_fonction (id)');
        $this->addSql('CREATE INDEX IDX_5D9F75A14DBFE673 ON employee (categorie_fonction_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A14DBFE673');
        $this->addSql('DROP TABLE categorie_fonction');
        $this->addSql('DROP INDEX IDX_5D9F75A14DBFE673 ON employee');
        $this->addSql('ALTER TABLE employee DROP categorie_fonction_id');
    }
}
