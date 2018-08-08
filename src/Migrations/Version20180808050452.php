<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180808050452 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE actors_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE actors_contacts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE actors_translations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE actors (id INT NOT NULL, original_name VARCHAR(100) NOT NULL, photo VARCHAR(255) DEFAULT NULL, imdb_id VARCHAR(20) DEFAULT NULL, birthday DATE DEFAULT NULL, gender INT NOT NULL, tmdb_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DF2BF0E555BCC5E5 ON actors (tmdb_id)');
        $this->addSql('CREATE TABLE actors_contacts (id INT NOT NULL, actor_id INT NOT NULL, provider VARCHAR(30) NOT NULL, url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BC98436110DAF24A ON actors_contacts (actor_id)');
        $this->addSql('CREATE TABLE actors_translations (id INT NOT NULL, actor_id INT NOT NULL, locale VARCHAR(5) NOT NULL, name VARCHAR(100) NOT NULL, place_of_birth VARCHAR(100) DEFAULT NULL, biography TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6070C63710DAF24A ON actors_translations (actor_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_ActorTranslations_locale_actor_id ON actors_translations (locale, actor_id)');
        $this->addSql('ALTER TABLE actors_contacts ADD CONSTRAINT FK_BC98436110DAF24A FOREIGN KEY (actor_id) REFERENCES actors (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE actors_translations ADD CONSTRAINT FK_6070C63710DAF24A FOREIGN KEY (actor_id) REFERENCES actors (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE actors_contacts DROP CONSTRAINT FK_BC98436110DAF24A');
        $this->addSql('ALTER TABLE actors_translations DROP CONSTRAINT FK_6070C63710DAF24A');
        $this->addSql('DROP SEQUENCE actors_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE actors_contacts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE actors_translations_id_seq CASCADE');
        $this->addSql('DROP TABLE actors');
        $this->addSql('DROP TABLE actors_contacts');
        $this->addSql('DROP TABLE actors_translations');
    }
}
