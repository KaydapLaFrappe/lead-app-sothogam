<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213101656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE lead CHANGE interlocuteur_id interlocuteur_id INT DEFAULT NULL, CHANGE date_reponse date_reponse DATE DEFAULT NULL, CHANGE orientation_client orientation_client VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interlocuteur CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE lead CHANGE interlocuteur_id interlocuteur_id INT UNSIGNED DEFAULT NULL, CHANGE date_reponse date_reponse DATE NOT NULL, CHANGE orientation_client orientation_client VARCHAR(255) NOT NULL');
    }
}
