<?php

namespace App\Movies\Command;

use App\Movies\Service\ReleaseDateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunReleaseDateQueue extends Command
{
    private $releaseDateService;

    public function __construct(ReleaseDateService $releaseDateService, ?string $name = null)
    {
        parent::__construct($name);

        $this->releaseDateService = $releaseDateService;
    }

    protected function configure()
    {
        $this
            ->setName('app:run-release-date-queue')
            ->setDescription('Check new release dates')
            ->setHelp('This command will search for new release dates on resources like imdb and tmdb');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Queue started at %s', \date('d-m-Y h:i:s')));
        $this->releaseDateService->runCheck();
        $output->writeln(sprintf('Queue ended at %s', \date('d-m-Y h:i:s')));
    }
}
