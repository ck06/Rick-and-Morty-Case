<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240727235550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rnm_character DROP remote_id, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE rnm_episode DROP remote_id, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE rnm_location DROP remote_id, CHANGE id id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rnm_episode ADD remote_id INT NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE rnm_location ADD remote_id INT NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE rnm_character ADD remote_id INT NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}
