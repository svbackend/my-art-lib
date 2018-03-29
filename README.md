## my-art-lib

Project where users can fill own library of watched movies and share it. (dev stage)

Pre-requirements:

* `php >= 7.2`

Install:

* `git clone`
* `composer update`
* `docker-compose up --build`
* `docker-compose exec app bash`
* `php bin/console doctrine:schema:update --force`

You'r ready!

To run project again simply type `docker-compose up` (without `--build` at this time)