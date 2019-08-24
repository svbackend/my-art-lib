<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190824185439 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE SEQUENCE movie_review_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE movie_review (id INT NOT NULL, answer_to_id INT DEFAULT NULL, movie_id INT NOT NULL, user_id INT NOT NULL, text TEXT NOT NULL, locale VARCHAR(2) NOT NULL, is_review BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_80841CB3AB0FA336 ON movie_review (answer_to_id)');
        $this->addSql('CREATE INDEX IDX_80841CB38F93B6FC ON movie_review (movie_id)');
        $this->addSql('CREATE INDEX IDX_80841CB3A76ED395 ON movie_review (user_id)');
        $this->addSql('ALTER TABLE movie_review ADD CONSTRAINT FK_80841CB3AB0FA336 FOREIGN KEY (answer_to_id) REFERENCES movie_review (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_review ADD CONSTRAINT FK_80841CB38F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_review ADD CONSTRAINT FK_80841CB3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE movie_review DROP CONSTRAINT FK_80841CB3AB0FA336');
        $this->addSql('DROP SEQUENCE movie_review_id_seq CASCADE');
        $this->addSql('DROP TABLE movie_review');
    }
}
