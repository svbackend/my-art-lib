<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/functions.php';

$environmentFile = __DIR__.'/../.env.test';
$isDbAlreadyCreatedFile = __DIR__.'/.db_already_created';

(new Dotenv())->load($environmentFile);

// Create and boot 'test' kernel
$kernel = new Kernel(\getenv('APP_ENV'), (bool) \getenv('APP_DEBUG'));
$kernel->boot();
// Create new application
$application = new Application($kernel);
$application->setAutoExit(false);

$isDbAlreadyCreated = file_exists($isDbAlreadyCreatedFile);

// Add the doctrine:database:drop command to the application and run it
$dropDatabaseDoctrineCommand = function () use ($application, $isDbAlreadyCreated) {
    if ($isDbAlreadyCreated === true) {
        return;
    }

    $input = new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--force' => true,
        '--if-exists' => true,
    ]);
    $input->setInteractive(false);
    $consoleOutput = new ConsoleOutput();
    $application->run($input, $consoleOutput);
};

// Add the doctrine:database:create command to the application and run it
$createDatabaseDoctrineCommand = function () use ($application, $isDbAlreadyCreated, $isDbAlreadyCreatedFile) {
    if ($isDbAlreadyCreated === true) {
        return;
    }

    exec('touch ' . $isDbAlreadyCreatedFile);
    $input = new ArrayInput([
        'command' => 'doctrine:database:create',
    ]);
    $input->setInteractive(false);
    $application->run($input, new ConsoleOutput());
};

$updateSchemaDoctrineCommand = function () use ($application) {
    $input = new ArrayInput([
        'command' => 'doctrine:migrations:migrate',
        '--no-interaction' => true,
    ]);
    $input->setInteractive(false);
    $application->run($input, new ConsoleOutput());
};

// Add the doctrine:fixtures:load command to the application and run it
$loadFixturesDoctrineCommand = function () use ($application) {
    $input = new ArrayInput([
        'command' => 'doctrine:fixtures:load',
        '--no-interaction' => true,
        '--purge-with-truncate' => true,
    ]);
    $input->setInteractive(false);
    $application->run($input, new ConsoleOutput());
};

// And finally call each of initialize functions to make test environment ready
\array_map(
    '\call_user_func',
    [
        $dropDatabaseDoctrineCommand,
        $createDatabaseDoctrineCommand,
        $updateSchemaDoctrineCommand,
        $loadFixturesDoctrineCommand,
    ]
);