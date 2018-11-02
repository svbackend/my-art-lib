<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181028155717 extends AbstractMigration
{
    private $countries = [
        'UKR' => 'Ukraine',
        'POL' => 'Poland',
        'BLR' => 'Belarus',
        'RUS' => 'Russia',
        'ESP' => 'Spain',
        'CAN' => 'Canada',
        'USA' => 'USA',
    ];

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE movies_release_dates_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE release_date_queue_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE countries_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE movies_release_dates (id INT NOT NULL, movie_id INT NOT NULL, country_code VARCHAR(3) NOT NULL, date DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_69ECF8158F93B6FC ON movies_release_dates (movie_id)');
        $this->addSql('CREATE INDEX IDX_69ECF815F026BB7C ON movies_release_dates (country_code)');
        $this->addSql('CREATE UNIQUE INDEX Movie_id_Country_code ON movies_release_dates (movie_id, country_code)');
        $this->addSql('CREATE TABLE release_date_queue (id INT NOT NULL, movie_id INT NOT NULL, added_at DATE DEFAULT NULL, is_active INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A8EA9D558F93B6FC ON release_date_queue (movie_id)');
        $this->addSql('CREATE TABLE countries (id INT NOT NULL, code VARCHAR(3) NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5D66EBAD77153098 ON countries (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5D66EBAD5E237E06 ON countries (name)');
        $this->addSql('ALTER TABLE movies_release_dates ADD CONSTRAINT FK_69ECF8158F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movies_release_dates ADD CONSTRAINT FK_69ECF815F026BB7C FOREIGN KEY (country_code) REFERENCES countries (code) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE release_date_queue ADD CONSTRAINT FK_A8EA9D558F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        foreach ($this->countries as $code => $name) {
            $this->addSql("INSERT INTO countries (id, code, name) VALUES (NEXTVAL('countries_id_seq'), '{$code}', '{$name}');");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE movies_release_dates DROP CONSTRAINT FK_69ECF815F026BB7C');
        $this->addSql('DROP SEQUENCE movies_release_dates_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE release_date_queue_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE countries_id_seq CASCADE');
        $this->addSql('DROP TABLE movies_release_dates');
        $this->addSql('DROP TABLE release_date_queue');
        $this->addSql('DROP TABLE countries');
    }
}
