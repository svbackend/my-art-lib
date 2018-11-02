<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181101141057 extends AbstractMigration
{
    private $imdbCountries = [
        'UKR' => 'Ukraine',
        'POL' => 'Poland',
        'BLR' => 'Belarus',
        'RUS' => 'Russia',
        'ESP' => 'Spain',
        'CAN' => 'Canada',
        'USA' => 'USA',
    ];

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE imdb_countries_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE imdb_countries (id INT NOT NULL, country_id INT NOT NULL, name VARCHAR(50) NOT NULL, alt_names VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A73E9C65E237E06 ON imdb_countries (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A73E9C6F92F3E70 ON imdb_countries (country_id)');
        $this->addSql('ALTER TABLE imdb_countries ADD CONSTRAINT FK_6A73E9C6F92F3E70 FOREIGN KEY (country_id) REFERENCES countries (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        foreach ($this->imdbCountries as $code => $imdbName) {
            $this->addSql("INSERT INTO imdb_countries (id, country_id, name, alt_names) VALUES (NEXTVAL('imdb_countries_id_seq'), (SELECT c.id FROM countries c WHERE c.code = '{$code}'), '{$imdbName}', '');");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE imdb_countries_id_seq CASCADE');
        $this->addSql('DROP TABLE imdb_countries');
    }
}
