<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181030094901 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $currentDate = \date('Y-m-d');
        $movies = $this->connection->executeQuery(
            'SELECT m.id, m.imdb_id FROM movies m WHERE m.release_date IS NOT NULL AND m.release_date::date > \''.$currentDate.'\''
        );

        foreach ($movies as $movie) {
            $isActive = $movie['imdb_id'] ? 1 : 0;
            $this->addSql("INSERT INTO release_date_queue (id, movie_id, added_at, is_active) 
                            VALUES 
                           (NEXTVAL('release_date_queue_id_seq'), {$movie['id']}, '{$currentDate}', {$isActive})");
        }
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("DELETE FROM release_date_queue;");
    }
}
