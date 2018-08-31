<?php
namespace Deployer;

require 'recipe/symfony4.php';

// Project name
set('application', 'mykino.top');

// Project repository
set('repository', 'git@github.com:svbackend/my-art-lib.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys 
add('shared_files', ['docker-compose.prod.yml']);
add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', []);

// Hosts
host('159.89.14.82')
    ->user('svbackend')
    ->identityFile('~/.ssh/dokey')
    ->set('deploy_path', '~/{{application}}');

set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader --ignore-platform-reqs --no-scripts');

// Tasks
task('build', function () {
    run('cd {{release_path}} && build');
});

task('deploy:cache:clear', function () {
    run('{{bin/console}} cache:clear --no-warmup --env=prod');
});

task('deploy:cache:warmup', function () {
    run('{{bin/console}} cache:warmup --env=prod');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

task('deploy:docker', function () {
    run('cd {{release_path}} && docker-compose down');
    run('cd {{release_path}} && docker-compose up -d');
});
before('deploy:symlink', 'deploy:docker');

task('deploy:db', function () {
    run('cd {{release_path}} && docker-compose exec app php bin/console doctrine:migrations:migrate');
});
after('deploy:docker', 'deploy:db');

// Migrate database before symlink new release.

//before('deploy:symlink', 'database:migrate');

