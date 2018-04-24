## my-art-lib

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
* Add new crontab line: `* * * * * docker-compose exec app bin/console swiftmailer:spool:send`
* `docker-compose exec app bash`
* `php bin/console doctrine:schema:update --force`
* `php bin/phpunit`

If all tests passed then you'r ready!

To run project again simply type `docker-compose up` (without `--build` at this time)

### Api Documentation

Can be found at: http://127.0.0.1:8080/api/doc

### Translations

Web interface for translations (only in dev env) can be found at: http://127.0.0.1:8080/admin/_trans

Feel free to translate messages and commit changes.