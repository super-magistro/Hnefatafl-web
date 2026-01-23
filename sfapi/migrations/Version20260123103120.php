<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123103120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C1209D2FA');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C3DCF2376');
        $this->addSql('DROP INDEX IDX_232B318C1209D2FA ON game');
        $this->addSql('DROP INDEX IDX_232B318C3DCF2376 ON game');
        $this->addSql('ALTER TABLE game ADD attacker_id INT NOT NULL, ADD defender_id INT NOT NULL, ADD game_board_id INT NOT NULL, ADD time_control VARCHAR(20) NOT NULL, ADD moves JSON NOT NULL COMMENT \'(DC2Type:json)\', ADD attacker_time_left INT DEFAULT NULL, ADD defender_time_left INT DEFAULT NULL, DROP player_attacker_id, DROP player_defender_id, CHANGE variant variant VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C65F8CAE3 FOREIGN KEY (attacker_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C4A3E3B6F FOREIGN KEY (defender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C4CA8A8A3 FOREIGN KEY (game_board_id) REFERENCES game_board (id)');
        $this->addSql('CREATE INDEX IDX_232B318C65F8CAE3 ON game (attacker_id)');
        $this->addSql('CREATE INDEX IDX_232B318C4A3E3B6F ON game (defender_id)');
        $this->addSql('CREATE INDEX IDX_232B318C4CA8A8A3 ON game (game_board_id)');
        $this->addSql('ALTER TABLE game_board CHANGE rules rules JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user ADD elo INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C65F8CAE3');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C4A3E3B6F');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C4CA8A8A3');
        $this->addSql('DROP INDEX IDX_232B318C65F8CAE3 ON game');
        $this->addSql('DROP INDEX IDX_232B318C4A3E3B6F ON game');
        $this->addSql('DROP INDEX IDX_232B318C4CA8A8A3 ON game');
        $this->addSql('ALTER TABLE game ADD player_attacker_id INT NOT NULL, ADD player_defender_id INT NOT NULL, DROP attacker_id, DROP defender_id, DROP game_board_id, DROP time_control, DROP moves, DROP attacker_time_left, DROP defender_time_left, CHANGE variant variant VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C1209D2FA FOREIGN KEY (player_defender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C3DCF2376 FOREIGN KEY (player_attacker_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_232B318C1209D2FA ON game (player_defender_id)');
        $this->addSql('CREATE INDEX IDX_232B318C3DCF2376 ON game (player_attacker_id)');
        $this->addSql('ALTER TABLE user DROP elo');
        $this->addSql('ALTER TABLE game_board CHANGE rules rules LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
