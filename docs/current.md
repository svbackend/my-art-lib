## Documentation

Warning: Some examples in this documentation can be simplified for better readability, it's not best practices and single way to do things, just use common sense.

### Most used commands

* `php bin/console doctrine:schema:update --force`
* `php bin/console doctrine:migr:migr`
* `php bin/console doctrine:fixtures:load --purge-with-truncate`
* `bin/console enqueue:consume --setup-broker -vvv`
* `php vendor/bin/php-cs-fixer fix src`
* `php bin/phpunit`

### Composer

Composer not included in docker container by default so to use it you should have it on your machine and run it like: 

```
docker run --rm --interactive --tty \
    --volume $PWD:/app \
    --user $(id -u):$(id -g) \
    composer "$@"
```

Or use a shortcut:

```
bin/composer -v
```

### Request objects

Usually we don't use Symfony Forms but instead we have Request Objects which can be used to validate request data.
By default request object should provide 2 methods: getErrorResponse() and rules() but if you want to use default error response you can extend your Request Object from `App\Request\BaseRequest` (Request/BaseRequest.php)

### Routing
Keep your routes in annotations but don't forget to add your controllers to router configuration:
```
# config/routes.yaml
...
genres:
    resource: ../src/Genres/Controller
    type: annotation
...
```

## Response

### Entity attributes (serializer groups)
To define which attributes will be displayed you can use serializer groups:
```
 /**
  * @Groups({"list", "view"})
  */
 private $id;
```
Or, if you want to show attribute only for some specific role then just define groups like this:
```
 /**
  * @Groups({"ROLE_ADMIN"})
  */
 private $email; // This attr will be displayed only for admins
```
And then just define which groups will be used in your controller:
```
return $this->response($items, 200, [], [
            'groups' => ['list'] // don't add ROLE_ADMIN - it's already done automatically 
        ]);
```
NOTICE: You don't need to add roles groups because it will be added automatically

### Translated entities
To create entity with content translations you can take Genre and GenreTranslations + take a look at  [GenreManageService](../src/Genres/Service/GenreManageService.php) as example.

But we have some magic to translate entities automatically before response:
when you type in your controller something like 
```
return $this->response($translatedEntityRepository->findAll()); // array of translated entites
// OR
return $this->response($singleTranslatedEntity);
```
All entities (or single entity) will be translated to user's current locale.

To get more info about "what is going on" when you returning entity with translations as response you can look at [TranslatedResponseTrait](../src/Translation/TranslatedResponseTrait.php)

To make changes in translations or add new ones you can use TranslatedEntityHelper, example:
```
$genre = new Genre();

$translations = [
    ['locale' => 'en', 'name' => 'New Genre Name'],
    ['locale' => 'ru', 'name' => 'Новое имя жанра'],
    ['locale' => 'pl', 'name' => 'Nowa nazwa gatunku'],
];

$addTranslation = function (array $translation) use ($genre) {
    $genre->addTranslation(
        new GenreTranslations($genre, $translation['locale'], $translation['name'])
    );
};

$updateTranslation = function (array $translation, GenreTranslations $oldTranslation) {
    // Will be called when translation with locale defined in $translation['locale'] already exists in $genre
    // You can change only needed attributes
    $oldTranslation->changeName($translation['name']);
};

$this->translatedEntityHelper->updateTranslations($genre, $translations, $addTranslation, $updateTranslation);
```

### Pagination
To return only some part of items you need to use pagination, it's can be easy implemented by
following few steps:
```
// Provide some common data - offset and limit
$offset = (int)$request->get('offset', 0);
$limit = $request->get('limit', null);

// Then build your query:
$query = $userRepository->createQueryBuilder('u')->where('...')->orderBy('...')->getQuery();

// And create PaginatedCollection:
$users = new \App\Pagination\PaginatedCollection($query, $offset, $limit);

return $this->response($users);
```

## Other

### Locale (User Language)

Can be defined as query param: GET api/genres?language=pl

Look at: [LocaleListener](../src/Translation/EventListener/LocaleListener.php)

Or, if not defined, would be specified automatically by symfony.

### Testing

##### Functional
We're using [dmaicher/doctrine-test-bundle](https://github.com/dmaicher/doctrine-test-bundle) so after each test your database changes will be lost.

To test user based functionality you can use predefined constants:
[UsersFixtures](../src/Users/DataFixtures/UsersFixtures.php)
(and feel free to create new ones).

So if, for example, you want to test something like authorized user simply add query param:
```
$userApiToken = UsersFixtures::TESTER_API_TOKEN; // api token with role ROLE_USER
$client->request('GET', "/api/genres?api_token={$userApiToken}");
```

##### Unit
todo

### CLI

* Application: `docker-compose exec app bash`
* Postgres: `docker-compose exec postgres bash`
* Redis: `docker-compose exec redis bash`

### pgAdmin

If you need web interface to manage ur databases set-up new postgres server for pgAdmin:

* Open in browser pg Admin (pgAdmin default host is: http://127.0.0.1:5050)
* Default login and pass: pgadmin4@pgadmin.org + admin
* Create new server and define server name to display
* Switch to "Connection" tab and use this data: 
* -Host: postgres
* -Port: 5432
* -Username: my_art_lib
* -Password: my_art_lib
* Save

Done!

Notice: test database (defined in .env.test) will be truncated before each test run so don't put any important data there.


