<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211141211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lead ADD categorie_demandeur INT NOT NULL, ADD nom_societe VARCHAR(255) NOT NULL, ADD nom VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD adresse_ligne1 VARCHAR(255) NOT NULL, ADD adresse_cp VARCHAR(255) NOT NULL, ADD adresse_ville VARCHAR(255) NOT NULL, ADD activite_demandeur VARCHAR(255) NOT NULL, ADD mail VARCHAR(255) NOT NULL, ADD tel VARCHAR(255) NOT NULL, ADD date_reponse DATE NOT NULL, ADD orientation_client VARCHAR(255) NOT NULL, ADD collection_category VARCHAR(255) NOT NULL, ADD numero_genere VARCHAR(255) NOT NULL, ADD envoye_se INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lead DROP categorie_demandeur, DROP nom_societe, DROP nom, DROP prenom, DROP adresse_ligne1, DROP adresse_cp, DROP adresse_ville, DROP activite_demandeur, DROP mail, DROP tel, DROP date_reponse, DROP orientation_client, DROP collection_category, DROP numero_genere, DROP envoye_se');
    }
}
