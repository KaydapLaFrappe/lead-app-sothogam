<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225125200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('CREATE TABLE tparametre (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        //$this->addSql('ALTER TABLE lead CHANGE source source VARCHAR(255) NOT NULL');
        //$this->addSql('ALTER TABLE lead ADD CONSTRAINT FK_289161CB44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        //$this->addSql('CREATE UNIQUE INDEX UNIQ_289161CB44AC3583 ON lead (operation_id)');
        //$this->addSql('DROP INDEX IDX_1981A66D19EB6921 ON operation');
        //$this->addSql('ALTER TABLE operation DROP client_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE tparametre');
        $this->addSql('ALTER TABLE lead DROP FOREIGN KEY FK_289161CB44AC3583');
        $this->addSql('DROP INDEX UNIQ_289161CB44AC3583 ON lead');
        $this->addSql('ALTER TABLE lead CHANGE source source VARCHAR(255) DEFAULT NULL, CHANGE collection_category collection_category VARCHAR(255) DEFAULT NULL, CHANGE numero_genere numero_genere VARCHAR(255) DEFAULT NULL, CHANGE envoye_se envoye_se INT DEFAULT NULL, CHANGE id_batiproduit id_batiproduit INT DEFAULT NULL');
        $this->addSql('ALTER TABLE operation ADD client_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_1981A66D19EB6921 ON operation (client_id)');
    }
}
