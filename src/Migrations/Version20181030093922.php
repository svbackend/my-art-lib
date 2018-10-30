<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181030093922 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE movies_release_dates DROP CONSTRAINT fk_69ecf815f026bb7c');
        $this->addSql('DROP INDEX idx_69ecf815f026bb7c');
        $this->addSql('DROP INDEX movie_id_country_code');
        $this->addSql('ALTER TABLE movies_release_dates ADD country_id INT NOT NULL');
        $this->addSql('ALTER TABLE movies_release_dates DROP country_code');
        $this->addSql('ALTER TABLE movies_release_dates ADD CONSTRAINT FK_69ECF815F92F3E70 FOREIGN KEY (country_id) REFERENCES countries (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_69ECF815F92F3E70 ON movies_release_dates (country_id)');
        $this->addSql('CREATE UNIQUE INDEX Movie_id_Country_id ON movies_release_dates (movie_id, country_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE movies_release_dates DROP CONSTRAINT FK_69ECF815F92F3E70');
        $this->addSql('DROP INDEX IDX_69ECF815F92F3E70');
        $this->addSql('DROP INDEX Movie_id_Country_id');
        $this->addSql('ALTER TABLE movies_release_dates ADD country_code VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE movies_release_dates DROP country_id');
        $this->addSql('ALTER TABLE movies_release_dates ADD CONSTRAINT fk_69ecf815f026bb7c FOREIGN KEY (country_code) REFERENCES countries (code) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_69ecf815f026bb7c ON movies_release_dates (country_code)');
        $this->addSql('CREATE UNIQUE INDEX movie_id_country_code ON movies_release_dates (movie_id, country_code)');
    }
}
