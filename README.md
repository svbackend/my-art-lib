## my-art-lib / [frontend](https://github.com/svbackend/my-art-lib-spa)

[mykino.top](https://mykino.top): Collect your own library of watched movies and get recommendation from other people

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/svbackend/my-art-lib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/svbackend/my-art-lib/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/svbackend/my-art-lib/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/svbackend/my-art-lib/?branch=master)
[![Build Status](https://travis-ci.org/svbackend/my-art-lib.svg?branch=master)](https://travis-ci.org/svbackend/my-art-lib)

Pre-requirements:

* `Docker & Composer & Git`
* `php 7.2`

Install:

* Copy and customize your docker-compose.yml: `cp docker-compose.yml.dist docker-compose.yml`
* `docker-compose up --build`
* `docker-compose exec app composer update`
* `cp .env.dist .env`
* `cp .env.dist .env.test`
* Change test db name: `sed -i 's/postgres\/my_art_lib/postgres\/my_art_lib_test/g' .env.test` 
* `docker-compose exec app bin/console doctrine:migr:migr`
* Optional - fixtures (you will be able to sign in as tester_fixture with pass 123456 and some other useful stuff): `php bin/console doctrine:fixtures:load --purge-with-truncate`
* Open .env + .env.test and set MOVIE_DB_API_KEY
* And finally to be sure that all is fine - run tests: `php bin/phpunit`

If all tests passed then you'r ready!

To run project again simply type `docker-compose up` (without `--build` at this time)

More information for developers can be found in [project docs](docs/current.md).

### Translations

Web interface for translations (only in dev env) can be found at: http://127.0.0.1:8080/admin/_trans

Feel free to translate messages and commit changes.