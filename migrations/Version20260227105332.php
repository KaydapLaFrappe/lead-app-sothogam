<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227105332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('CREATE TABLE lead_archived (id INT AUTO_INCREMENT NOT NULL, id_lead INT NOT NULL, date_creation DATE NOT NULL, categorie_demandeur INT NOT NULL, cp VARCHAR(255) NOT NULL, departement VARCHAR(255) NOT NULL, message VARCHAR(255) NOT NULL, activite_demandeur VARCHAR(255) NOT NULL, statut INT NOT NULL, date_reponse DATE NOT NULL, source INT NOT NULL, interlocuteur_id INT NOT NULL, orientation_client VARCHAR(255) NOT NULL, collection_category VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        //$this->addSql('ALTER TABLE lead CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE message message LONGTEXT NOT NULL, CHANGE nom_societe nom_societe VARCHAR(255) NOT NULL, CHANGE adresse_ligne1 adresse_ligne1 VARCHAR(255) NOT NULL, CHANGE collection_category collection_category VARCHAR(255) NOT NULL, CHANGE numero_genere numero_genere VARCHAR(255) NOT NULL, CHANGE envoye_se envoye_se INT NOT NULL, CHANGE id_batiproduit id_batiproduit INT NOT NULL, CHANGE departement departement VARCHAR(255) NOT NULL');
        //$this->addSql('ALTER TABLE lead ADD CONSTRAINT FK_289161CB44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        //$this->addSql('CREATE UNIQUE INDEX UNIQ_289161CB44AC3583 ON lead (operation_id)');
        //$this->addSql('DROP INDEX IDX_1981A66D19EB6921 ON operation');
        //$this->addSql('ALTER TABLE operation DROP client_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE lead_archived');
        $this->addSql('ALTER TABLE lead DROP FOREIGN KEY FK_289161CB44AC3583');
        $this->addSql('DROP INDEX UNIQ_289161CB44AC3583 ON lead');
        $this->addSql('ALTER TABLE lead CHANGE statut statut VARCHAR(255) DEFAULT \'0\' NOT NULL, CHANGE message message LONGTEXT DEFAULT NULL, CHANGE nom_societe nom_societe VARCHAR(255) DEFAULT NULL, CHANGE adresse_ligne1 adresse_ligne1 VARCHAR(255) DEFAULT NULL, CHANGE collection_category collection_category VARCHAR(255) DEFAULT NULL, CHANGE numero_genere numero_genere VARCHAR(255) DEFAULT NULL, CHANGE envoye_se envoye_se INT DEFAULT NULL, CHANGE id_batiproduit id_batiproduit INT DEFAULT NULL, CHANGE departement departement VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE operation ADD client_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_1981A66D19EB6921 ON operation (client_id)');
    }
}
