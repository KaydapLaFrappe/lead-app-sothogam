<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130094642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, nom_societe VARCHAR(255) NOT NULL, mail VARCHAR(255) NOT NULL, tel VARCHAR(20) NOT NULL, adresse_ligne VARCHAR(255) NOT NULL, adresse_cp VARCHAR(255) NOT NULL, adresse_ville VARCHAR(255) NOT NULL, departement VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE interlocuteur (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, mail VARCHAR(255) NOT NULL, portable VARCHAR(20) NOT NULL, statut VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lead (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, interlocuteur_id INT DEFAULT NULL, operation_id INT DEFAULT NULL, date_creation DATE NOT NULL, date_relance DATE NOT NULL, statut VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, source VARCHAR(255) NOT NULL, INDEX IDX_289161CB19EB6921 (client_id), INDEX IDX_289161CB5DC4D72E (interlocuteur_id), UNIQUE INDEX UNIQ_289161CB44AC3583 (operation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE operation (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, tdepartement_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, INDEX IDX_1981A66D19EB6921 (client_id), INDEX IDX_1981A66D46B125F1 (tdepartement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tdepartement (id INT AUTO_INCREMENT NOT NULL, code_postal VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lead ADD CONSTRAINT FK_289161CB19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE lead ADD CONSTRAINT FK_289161CB5DC4D72E FOREIGN KEY (interlocuteur_id) REFERENCES interlocuteur (id)');
        $this->addSql('ALTER TABLE lead ADD CONSTRAINT FK_289161CB44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66D19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66D46B125F1 FOREIGN KEY (tdepartement_id) REFERENCES tdepartement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lead DROP FOREIGN KEY FK_289161CB19EB6921');
        $this->addSql('ALTER TABLE lead DROP FOREIGN KEY FK_289161CB5DC4D72E');
        $this->addSql('ALTER TABLE lead DROP FOREIGN KEY FK_289161CB44AC3583');
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66D19EB6921');
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66D46B125F1');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE interlocuteur');
        $this->addSql('DROP TABLE lead');
        $this->addSql('DROP TABLE operation');
        $this->addSql('DROP TABLE tdepartement');
    }
}
