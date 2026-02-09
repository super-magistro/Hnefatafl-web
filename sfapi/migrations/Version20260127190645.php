<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127190645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_board (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, board_size INT NOT NULL, initial_layout JSON NOT NULL COMMENT \'(DC2Type:json)\', terrain_layout JSON NOT NULL COMMENT \'(DC2Type:json)\', rules JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C4CA8A8A3 FOREIGN KEY (game_board_id) REFERENCES game_board (id)');
        $this->addSql('CREATE INDEX IDX_232B318C4CA8A8A3 ON game (game_board_id)');
        $this->addSql('ALTER TABLE game RENAME INDEX fk_232b318c65f8cae3 TO IDX_232B318C65F8CAE3');
        $this->addSql('ALTER TABLE game RENAME INDEX fk_232b318c4a3e3b6f TO IDX_232B318C4A3E3B6F');
        $this->addSql('ALTER TABLE user ADD elo INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C4CA8A8A3');
        $this->addSql('DROP TABLE game_board');
        $this->addSql('ALTER TABLE user DROP elo');
        $this->addSql('DROP INDEX IDX_232B318C4CA8A8A3 ON game');
        $this->addSql('ALTER TABLE game RENAME INDEX idx_232b318c65f8cae3 TO FK_232B318C65F8CAE3');
        $this->addSql('ALTER TABLE game RENAME INDEX idx_232b318c4a3e3b6f TO FK_232B318C4A3E3B6F');
    }
}
