<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add prime_fonction table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE prime_fonction (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, periode_paie_id INT NOT NULL, taux_monetaire_fonction DOUBLE PRECISION NOT NULL, nombre_jours DOUBLE PRECISION NOT NULL, note_hierarchique DOUBLE PRECISION NOT NULL, montant_fonction DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) NOT NULL, calculated_at DATETIME DEFAULT NULL, INDEX IDX_91FC8C0E8C03F15C (employee_id), INDEX IDX_91FC8C0E196A46D7 (periode_paie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE prime_fonction ADD CONSTRAINT FK_91FC8C0E8C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE prime_fonction ADD CONSTRAINT FK_91FC8C0E196A46D7 FOREIGN KEY (periode_paie_id) REFERENCES periode_paie (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prime_fonction DROP FOREIGN KEY FK_91FC8C0E8C03F15C');
        $this->addSql('ALTER TABLE prime_fonction DROP FOREIGN KEY FK_91FC8C0E196A46D7');
        $this->addSql('DROP TABLE prime_fonction');
    }
}
