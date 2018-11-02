<?php declare(strict_types=1);

namespace DoctrineMigrations;

use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Service\ImdbIdLoaderService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181030094901 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $currentDate = \date('Y-m-d');
        $movies = $this->connection->executeQuery(
            'SELECT m.id, m.imdb_id, m.tmdb_id FROM movies m WHERE m.release_date IS NOT NULL AND m.release_date::date > \''.$currentDate.'\''
        );

        /** @var $imdbIdLoader ImdbIdLoaderService */
        $imdbIdLoader = $this->container->get(ImdbIdLoaderService::class);
        $i = 0;

        foreach ($movies as $movie) {
            $isActive = $movie['imdb_id'] ? 1 : 0;

            if ($i === 10) {
                $i = 0;
                sleep(5);
            }

            if ($isActive === 0) {
                $imdbId = null;
                $i++;
                try {
                    $imdbId = $imdbIdLoader->getImdbId((int)$movie['tmdb_id']);
                } catch (TmdbMovieNotFoundException $movieNotFoundException) {

                } catch (TmdbRequestLimitException $requestLimitException) {
                    sleep(5);
                }

                if ($imdbId) {
                    $isActive = 1;
                    $this->addSql("UPDATE movies SET imdb_id = '{$imdbId}' WHERE id = {$movie['id']}");
                }
            }

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
