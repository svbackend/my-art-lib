## my-art-lib

Project where users can fill own library of watched movies and share it. (dev stage)

Pre-requirements:

* `Docker`

Install:

* `git clone`
* `docker-compose up --build`
* `docker-compose exec app bash`
* `composer update`
* `php bin/console doctrine:schema:update --force`

You'r ready!

To run project again simply type `docker-compose up` (without `--build` at this time)

To get access to postgres cli type: 
* `docker-compose exec postgres bash`

To get access to redis cli type: 
* `docker-compose exec redis bash`