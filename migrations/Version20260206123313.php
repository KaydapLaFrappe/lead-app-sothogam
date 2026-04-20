<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206123313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur ADD chef_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE interlocuteur ADD CONSTRAINT FK_E3FB1424150A48F1 FOREIGN KEY (chef_id) REFERENCES interlocuteur (id)');
        $this->addSql('CREATE INDEX IDX_E3FB1424150A48F1 ON interlocuteur (chef_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur DROP FOREIGN KEY FK_E3FB1424150A48F1');
        $this->addSql('DROP INDEX IDX_E3FB1424150A48F1 ON interlocuteur');
        $this->addSql('ALTER TABLE interlocuteur DROP chef_id');
    }
}
