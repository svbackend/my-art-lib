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
* `docker-compose exec app bash`
* `php bin/console doctrine:schema:update --force`
* `php bin/phpunit`

If all tests passed then you'r ready!

To run project again simply type `docker-compose up` (without `--build` at this time)

To generate oauth2 client use this command: 
* `php bin/console oauth:create-client --redirect-uri="http://127.0.0.1:8080/" --grant-type="password"`

To get access to postgres cli type: 
* `docker-compose exec postgres bash`

To get access to redis cli type: 
* `docker-compose exec redis bash`