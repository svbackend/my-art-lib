<?php
namespace Deployer;

require 'recipe/symfony4.php';

// Project name
set('application', 'mykino.top');
set('default_timeout', 86400);
set('timeout', 86200);
set('ssh_multiplexing', true);

// Project repository
set('repository', 'git@github.com:svbackend/my-art-lib.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('http_user', 'www-data');

// Shared files/dirs between deploys
add('shared_dirs', [
    'public/f/movies',
    'public/f/actors',
]);
add('writable_dirs', [
    'public/f',
    'public/f/movies',
    'public/f/actors',
]);

// Hosts
host('142.93.109.174')
    ->user('deployer')
    ->identityFile('~/.ssh/dokey')
    ->set('deploy_path', '/var/www/mykino.top');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.
before('deploy:symlink', 'database:migrate');

