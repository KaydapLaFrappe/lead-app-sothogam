<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217091611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE lead CHANGE interlocuteur_id interlocuteur_id INT DEFAULT NULL, CHANGE collection_category collection_category VARCHAR(255) NOT NULL, CHANGE numero_genere numero_genere VARCHAR(255) NOT NULL, CHANGE envoye_se envoye_se INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE lead CHANGE interlocuteur_id interlocuteur_id INT UNSIGNED DEFAULT NULL, CHANGE collection_category collection_category VARCHAR(255) DEFAULT NULL, CHANGE numero_genere numero_genere VARCHAR(255) DEFAULT NULL, CHANGE envoye_se envoye_se INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_289161CB5DC4D72E ON lead (interlocuteur_id)');
    }
}
