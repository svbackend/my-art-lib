<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180912064422 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE users_interested_movies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE users_interested_movies (id INT NOT NULL, user_id INT NOT NULL, movie_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F2A24D01A76ED395 ON users_interested_movies (user_id)');
        $this->addSql('CREATE INDEX IDX_F2A24D018F93B6FC ON users_interested_movies (movie_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_UserInterestedMovie_user_id_movie_id ON users_interested_movies (user_id, movie_id)');
        $this->addSql('ALTER TABLE users_interested_movies ADD CONSTRAINT FK_F2A24D01A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_interested_movies ADD CONSTRAINT FK_F2A24D018F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE users_interested_movies_id_seq CASCADE');
        $this->addSql('DROP TABLE users_interested_movies');
    }
}
