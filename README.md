## my-art-lib / [frontend](https://github.com/svbackend/my-art-lib-spa)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/svbackend/my-art-lib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/svbackend/my-art-lib/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/svbackend/my-art-lib/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/svbackend/my-art-lib/?branch=master)
[![Build Status](https://travis-ci.org/svbackend/my-art-lib.svg?branch=master)](https://travis-ci.org/svbackend/my-art-lib)

Project where users can fill own library of watched movies and share it. (dev stage)

Pre-requirements:

* `Docker & Composer & Git`
* `php 7.2`

Install:

* `docker-compose up --build`
* `bin/composer update`
* `cp .env.dist .env`
* `cp .env.dist .env.test`
* Open .env.test and change db name: `DATABASE_URL="pgsql://my_art_lib:my_art_lib@postgres/test_my_art_lib"`
* `docker-compose exec app bash`
* `php bin/console doctrine:migr:migr`
* Not required, but recommended: `php bin/console doctrine:fixtures:load --purge-with-truncate`
* `php bin/phpunit`

If all tests passed then you'r ready!

To run project again simply type `docker-compose up` (without `--build` at this time)

To setup queue daemon run: `bin/console enqueue:consume --setup-broker -vvv`

More information for developers can be found in [project docs](docs/current.md).

### Translations

Web interface for translations (only in dev env) can be found at: http://127.0.0.1:8080/admin/_trans

Feel free to translate messages and commit changes.