<?php
namespace Deployer;

require 'recipe/symfony4.php';

// Project name
set('application', 'mykino.top');

// Project repository
set('repository', 'git@github.com:svbackend/my-art-lib.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('http_user', 'www-data');

// Shared files/dirs between deploys
set('shared_files', ['docker-compose.prod.yml']);
set('shared_dirs', []);
set('writable_dirs', ['var']);

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

task('deploy:env', function () {
    run('rm -f {{release_path}}/.env');
    run('cp {{deploy_path}}/shared/.env {{release_path}}/.env');
    run('cd {{release_path}} && chmod 755 -R var');
});
after('deploy:writable', 'deploy:env');

task('deploy:var_dir', function () {
    run('cd {{deploy_path}}/current && docker-compose down');
    run('rm -f {{release_path}}/.env');
    run('cp {{deploy_path}}/shared/.env {{release_path}}/.env');
    run('cd {{release_path}} && chmod 755 -R var');
});
after('deploy:cache:warmup', 'deploy:var_dir');

task('deploy:cache:clear', function () {
    run('{{bin/console}} cache:clear --no-warmup --env=prod');
});

task('deploy:cache:warmup', function () {
    run('{{bin/console}} cache:warmup --env=prod');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

task('deploy:docker', function () {
    run('cd {{release_path}} && docker-compose -f docker-compose.deploy.yml up -d');
});
after('deploy:symlink', 'deploy:docker');

task('deploy:db', function () {
    run('cd {{deploy_path}}/current && docker exec -i $(docker-compose ps -q app) php ./bin/console doctrine:migrations:migrate -n');
});
after('deploy:docker', 'deploy:db');

task('deploy:production', function () {
    run('cd {{deploy_path}}/current && docker-compose down');
    run('cd {{deploy_path}}/current && docker-compose -f docker-compose.prod.yml up -d');
});
after('deploy:db', 'deploy:production');

// Migrate database before symlink new release.

//before('deploy:symlink', 'database:migrate');

