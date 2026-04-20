<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225071248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        $this->addSql('ALTER TABLE interlocuteur CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE interlocuteur ADD CONSTRAINT FK_E3FB1424CCF9E01E FOREIGN KEY (departement_id) REFERENCES tdepartement (id)');
        //$this->addSql('ALTER TABLE lead ADD CONSTRAINT FK_289161CB44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        //$this->addSql('CREATE UNIQUE INDEX UNIQ_289161CB44AC3583 ON lead (operation_id)');
        //$this->addSql('DROP INDEX IDX_1981A66D19EB6921 ON operation');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        //$this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, prenom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, nom_societe VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, mail VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tel VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, adresse_ligne VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, adresse_cp VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, adresse_ville VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, departement VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE interlocuteur DROP FOREIGN KEY FK_E3FB1424CCF9E01E');
        $this->addSql('ALTER TABLE interlocuteur CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE lead DROP FOREIGN KEY FK_289161CB44AC3583');
        $this->addSql('DROP INDEX UNIQ_289161CB44AC3583 ON lead');
        $this->addSql('ALTER TABLE lead CHANGE source source VARCHAR(255) DEFAULT NULL, CHANGE activite_demandeur activite_demandeur VARCHAR(255) DEFAULT NULL, CHANGE collection_category collection_category VARCHAR(255) DEFAULT NULL, CHANGE numero_genere numero_genere VARCHAR(255) DEFAULT NULL, CHANGE envoye_se envoye_se INT DEFAULT NULL, CHANGE id_batiproduit id_batiproduit INT DEFAULT NULL');
        //$this->addSql('ALTER TABLE operation ADD client_id INT DEFAULT NULL');
        //$this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66D19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        //$this->addSql('CREATE INDEX IDX_1981A66D19EB6921 ON operation (client_id)');
    }
}
