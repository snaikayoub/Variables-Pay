<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260322002042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE voyage_deplacement ADD ville_depart_aller VARCHAR(255) DEFAULT NULL, ADD ville_arrivee_aller VARCHAR(255) DEFAULT NULL, ADD ville_depart_retour VARCHAR(255) DEFAULT NULL, ADD ville_arrivee_retour VARCHAR(255) DEFAULT NULL, ADD lat_depart_aller DOUBLE PRECISION DEFAULT NULL, ADD lon_depart_aller DOUBLE PRECISION DEFAULT NULL, ADD lat_arrivee_aller DOUBLE PRECISION DEFAULT NULL, ADD lon_arrivee_aller DOUBLE PRECISION DEFAULT NULL, ADD lat_depart_retour DOUBLE PRECISION DEFAULT NULL, ADD lon_depart_retour DOUBLE PRECISION DEFAULT NULL, ADD lat_arrivee_retour DOUBLE PRECISION DEFAULT NULL, ADD lon_arrivee_retour DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE voyage_deplacement DROP ville_depart_aller, DROP ville_arrivee_aller, DROP ville_depart_retour, DROP ville_arrivee_retour, DROP lat_depart_aller, DROP lon_depart_aller, DROP lat_arrivee_aller, DROP lon_arrivee_aller, DROP lat_depart_retour, DROP lon_depart_retour, DROP lat_arrivee_retour, DROP lon_arrivee_retour');
    }
}
