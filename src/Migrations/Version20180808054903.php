<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180808054903 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE movies_actors_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE movies_actors (id INT NOT NULL, movie_id INT NOT NULL, actor_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A85722518F93B6FC ON movies_actors (movie_id)');
        $this->addSql('CREATE INDEX IDX_A857225110DAF24A ON movies_actors (actor_id)');
        $this->addSql('CREATE UNIQUE INDEX Movie_id_Actor_id ON movies_actors (movie_id, actor_id)');
        $this->addSql('ALTER TABLE movies_actors ADD CONSTRAINT FK_A85722518F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movies_actors ADD CONSTRAINT FK_A857225110DAF24A FOREIGN KEY (actor_id) REFERENCES actors (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE movies_actors_id_seq CASCADE');
        $this->addSql('DROP TABLE movies_actors');
    }
}
