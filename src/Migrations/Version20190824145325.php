<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190824145325 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE movie_card_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE movie_card (id INT NOT NULL, movie_id INT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, locale VARCHAR(2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E572B9DE8F93B6FC ON movie_card (movie_id)');
        $this->addSql('CREATE INDEX IDX_E572B9DEA76ED395 ON movie_card (user_id)');
        $this->addSql('ALTER TABLE movie_card ADD CONSTRAINT FK_E572B9DE8F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_card ADD CONSTRAINT FK_E572B9DEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE movie_card_id_seq CASCADE');
        $this->addSql('DROP TABLE movie_card');
    }
}
