<?php

namespace App\Movies\Command;

use App\Movies\DTO\ReleaseDateNotificationDTO;
use App\Movies\Repository\MovieReleaseDateRepository;
use App\Users\Service\SendEmailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseDateNotifications extends Command
{
    /** @var $repository MovieReleaseDateRepository */
    private $repository;

    /** @var $emailService SendEmailService */
    private $emailService;

    public function __construct(MovieReleaseDateRepository $repository, SendEmailService $emailService, ?string $name = null)
    {
        parent::__construct($name);

        $this->repository = $repository;
        $this->emailService = $emailService;
    }

    protected function configure()
    {
        $this
            ->setName('app:release-date-notifications')
            ->setDescription('Send release date notifications if any movies released today')
            ->setHelp('This command will notify users about release dates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = date('Y-m-d');
        $rows = $this->repository->findAllByDate($date);
        $rows = $rows->getScalarResult();

        foreach ($rows as $row) {
            $dto = new ReleaseDateNotificationDTO(
                (int) $row['m_id'],
                $row['m_original_title'],
                $row['c_name']
            );
            $this->emailService->sendReleaseDateNotification($row['u_email'], $dto);
        }
    }
}
