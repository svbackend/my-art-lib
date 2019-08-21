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
set('http_user', 'deployer');

// Shared files/dirs between deploys
add('shared_dirs', [
    'public/f/movies',
    'public/f/actors',
]);

// Hosts
host('142.93.109.174')
    ->user('deployer')
    ->identityFile('~/.ssh/dokey')
    ->set('deploy_path', '/var/www/mykino.top');

task('apache:restart', function() {
    run('sudo systemctl reload apache2');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'supervisord:restart');

// Migrate database before symlink new release.
before('deploy:symlink', 'database:migrate');

after('deploy:symlink', 'apache:restart');

after('apache:restart', 'supervisord:restart');

task('supervisord:restart', function() {
    try {
        run('kill -15 $(cat /tmp/supervisord.pid)');
    } catch (\Throwable $e) {
        writeln('/tmp/supervisord.pid not found');
    }
    run('cd /etc && supervisord');
});

after('supervisord:restart', 'crontab:update');

task('crontab:update', function() {
    run('crontab /var/www/mykino.top/current/.docker/crontab');
    run('crontab -l');
});