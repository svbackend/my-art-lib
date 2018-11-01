<?php

namespace App\Movies\Command;

use App\Movies\Repository\MovieReleaseDateRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseDateNotifications extends Command
{
    /** @var $repository MovieReleaseDateRepository */
    private $repository;

    public function __construct(MovieReleaseDateRepository $repository, ?string $name = null)
    {
        parent::__construct($name);

        $this->repository = $repository;
    }

    protected function configure()
    {
        $this
            ->setName('app:release-date-notifications')
            ->setDescription('Send release date notifications if any movies released today')
            ->setHelp('This command will search for new release dates on resources like imdb and tmdb');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = date('Y-m-d');
        $rows = $this->repository->findAllByDate($date);
        $rows = $rows->getArrayResult();

        foreach ($rows as $row) {
            // todo send email notifications to user that movie available in cinemas of his country
        }
    }
}