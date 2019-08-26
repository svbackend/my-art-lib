<?php

namespace App\Movies\Command;

use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieCard;
use App\Movies\Parser\Megogo;
use App\Movies\Repository\MovieCardRepository;
use App\Movies\Repository\MovieRepository;
use App\Users\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadWatchCards extends Command
{
    /** @var $repository MovieRepository */
    private $repository;

    private $userRepository;

    /** @var $parser Megogo */
    private $parser;

    private $em;
    private $cache;

    private const CACHE_TIME = 2592000; // 30 days

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository, MovieCardRepository $cardRepository, MovieRepository $repository, Megogo $parser, CacheInterface $cache, ?string $name = null)
    {
        parent::__construct($name);

        $this->repository = $repository;
        $this->cardRepository = $cardRepository;
        $this->userRepository = $userRepository;
        $this->parser = $parser;
        $this->cache = $cache;
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('app:load-watch-cards')
            ->setDescription('Find movies on megogo')
            ->setHelp('php bin/console app:load-watch-cards');
    }

    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $title = 'Megogo';
        $locale = 'uk';
        $movies = $this->repository->findAllWithoutCardsQuery($title, $locale)->getResult();
        $user = $this->userRepository->findOneBy([
            'username' => 'support'
        ]);

        /** @var $movie Movie */
        foreach ($movies as $movie) {
            $cacheKey = 'megogo_notfound_' . $movie->getId();
            if ($this->cache->has($cacheKey)) {
                continue;
            }

            try {
                $url = $this->parser->getUrlByTitle($movie->getOriginalTitle());
            } catch (\ErrorException $e) {
                $this->cache->set($cacheKey, true, self::CACHE_TIME);
                $o->writeln(sprintf(
                    '[LoadWatchCards] [%s]: %s', $movie->getOriginalTitle(), $e->getMessage()
                ));
                continue;
            }

            $description = sprintf('Дивитись онлайн в хорошій якості у найбільшому онлайн кінотеатрі у східній Європі');
            $card = new MovieCard($movie, $user, $locale, $title, $description, MovieCard::TYPE_WATCH, $url);

            $this->em->persist($card);
            $this->em->flush();
        }
    }
}