<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240726234314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rnm_character (id INT NOT NULL, origin_location_id INT DEFAULT NULL, location_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, species VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, gender VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B293923BE99C7F9 (origin_location_id), INDEX IDX_B293923B64D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rnm_character_episode (character_id INT NOT NULL, episode_id INT NOT NULL, INDEX IDX_F8E7EA001136BE75 (character_id), INDEX IDX_F8E7EA00362B62A0 (episode_id), PRIMARY KEY(character_id, episode_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rnm_episode (id INT NOT NULL, name VARCHAR(255) NOT NULL, air_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', episode_string VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rnm_location (id INT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, dimension VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rnm_character ADD CONSTRAINT FK_B293923BE99C7F9 FOREIGN KEY (origin_location_id) REFERENCES rnm_location (id)');
        $this->addSql('ALTER TABLE rnm_character ADD CONSTRAINT FK_B293923B64D218E FOREIGN KEY (location_id) REFERENCES rnm_location (id)');
        $this->addSql('ALTER TABLE rnm_character_episode ADD CONSTRAINT FK_F8E7EA001136BE75 FOREIGN KEY (character_id) REFERENCES rnm_character (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rnm_character_episode ADD CONSTRAINT FK_F8E7EA00362B62A0 FOREIGN KEY (episode_id) REFERENCES rnm_episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB03464D218E');
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB034E99C7F9');
        $this->addSql('ALTER TABLE character_episode DROP FOREIGN KEY FK_B40F9CE71136BE75');
        $this->addSql('ALTER TABLE character_episode DROP FOREIGN KEY FK_B40F9CE7362B62A0');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE character_episode');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE episode');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `character` (id INT NOT NULL, origin_location_id INT DEFAULT NULL, location_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, species VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, gender VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_937AB03464D218E (location_id), INDEX IDX_937AB034E99C7F9 (origin_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE character_episode (character_id INT NOT NULL, episode_id INT NOT NULL, INDEX IDX_B40F9CE7362B62A0 (episode_id), INDEX IDX_B40F9CE71136BE75 (character_id), PRIMARY KEY(character_id, episode_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE location (id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, dimension VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE episode (id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, air_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', episode_string VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB03464D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB034E99C7F9 FOREIGN KEY (origin_location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE character_episode ADD CONSTRAINT FK_B40F9CE71136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE character_episode ADD CONSTRAINT FK_B40F9CE7362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rnm_character DROP FOREIGN KEY FK_B293923BE99C7F9');
        $this->addSql('ALTER TABLE rnm_character DROP FOREIGN KEY FK_B293923B64D218E');
        $this->addSql('ALTER TABLE rnm_character_episode DROP FOREIGN KEY FK_F8E7EA001136BE75');
        $this->addSql('ALTER TABLE rnm_character_episode DROP FOREIGN KEY FK_F8E7EA00362B62A0');
        $this->addSql('DROP TABLE rnm_character');
        $this->addSql('DROP TABLE rnm_character_episode');
        $this->addSql('DROP TABLE rnm_episode');
        $this->addSql('DROP TABLE rnm_location');
    }
}
