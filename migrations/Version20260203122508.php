<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203122508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur ADD departement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE interlocuteur ADD CONSTRAINT FK_E3FB1424CCF9E01E FOREIGN KEY (departement_id) REFERENCES tdepartement (id)');
        $this->addSql('CREATE INDEX IDX_E3FB1424CCF9E01E ON interlocuteur (departement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur DROP FOREIGN KEY FK_E3FB1424CCF9E01E');
        $this->addSql('DROP INDEX IDX_E3FB1424CCF9E01E ON interlocuteur');
        $this->addSql('ALTER TABLE interlocuteur DROP departement_id');
    }
}
