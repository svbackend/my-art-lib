<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180804082737 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $pass = '';
        $roles = '["ROLE_USER","ROLE_ADMIN"]';
        $this->addSql("INSERT INTO users_profiles (id, first_name, last_name, birth_date, about, public_email) VALUES (1, 'Support', 'Support', NULL, NULL, 'svbackend22@gmail.com')");
        $this->addSql("INSERT INTO users (id, profile_id, email, is_email_confirmed, username, password, roles) VALUES (1, 1, 'svbackend22@gmail.com', 1, 'support', '$pass', '$roles')");

        $genresJson = $this->getGenresJson();
        $genres = json_decode($genresJson, true);

        foreach ($genres as $genre) {
            $this->addSql("INSERT INTO genres (id, tmdb_id) VALUES (NEXTVAL('genres_id_seq'), {$genre['id']})");

            foreach ($genre['translations'] as $locale => $name) {
                $this->addSql("INSERT INTO genres_translations (id, genre_id, locale, \"name\") VALUES (NEXTVAL('genres_translations_id_seq'), CURRVAL('genres_id_seq'), '$locale', '$name')");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM users WHERE id = 1');
        $this->addSql('DELETE FROM users_profiles WHERE id = 1');
        $this->addSql('DELETE FROM genres WHERE tmdb_id > 0');
    }

    private function getGenresJson(): string
    {
        return <<<JSON
[
  {
    "id": 28,
    "name": "Action",
    "translations": {
      "en": "Action",
      "ru": "Экшн",
      "uk": "Екшн",
      "pl": "Action"
    }
  },
  {
    "id": 12,
    "name": "Adventure",
    "translations": {
      "en": "Adventure",
      "ru": "Приключения",
      "uk": "Пригоди",
      "pl": "Adventure"
    }
  },
  {
    "id": 16,
    "name": "Animation",
    "translations": {
      "en": "Animation",
      "ru": "Анимация",
      "uk": "Анімація",
      "pl": "Animation"
    }
  },
  {
    "id": 35,
    "name": "Comedy",
    "translations": {
      "en": "Comedy",
      "ru": "Комедия",
      "uk": "Комедія",
      "pl": "Comedy"
    }
  },
  {
    "id": 80,
    "name": "Crime",
    "translations": {
      "en": "Crime",
      "ru": "Криминальный",
      "uk": "Кримінальний",
      "pl": "Crime"
    }
  },
  {
    "id": 99,
    "name": "Documentary",
    "translations": {
      "en": "Documentary",
      "ru": "Документальный",
      "uk": "Документальний",
      "pl": "Documentary"
    }
  },
  {
    "id": 18,
    "name": "Drama",
    "translations": {
      "en": "Drama",
      "ru": "Драма",
      "uk": "Драма",
      "pl": "Drama"
    }
  },
  {
    "id": 10751,
    "name": "Family",
    "translations": {
      "en": "Family",
      "ru": "Семейный",
      "uk": "Сімейний",
      "pl": "Family"
    }
  },
  {
    "id": 14,
    "name": "Fantasy",
    "translations": {
      "en": "Fantasy",
      "ru": "Фэнтези",
      "uk": "Фентезі",
      "pl": "Fantasy"
    }
  },
  {
    "id": 36,
    "name": "History",
    "translations": {
      "en": "History",
      "ru": "Исторический",
      "uk": "Історичний",
      "pl": "History"
    }
  },
  {
    "id": 27,
    "name": "Horror",
    "translations": {
      "en": "Horror",
      "ru": "Ужасы",
      "uk": "Жахи",
      "pl": "Horror"
    }
  },
  {
    "id": 10402,
    "name": "Music",
    "translations": {
      "en": "Music",
      "ru": "Мюзикл",
      "uk": "Мюзікл",
      "pl": "Music"
    }
  },
  {
    "id": 9648,
    "name": "Mystery",
    "translations": {
      "en": "Mystery",
      "ru": "Мистика",
      "uk": "Містика",
      "pl": "Mystery"
    }
  },
  {
    "id": 10749,
    "name": "Romance",
    "translations": {
      "en": "Romance",
      "ru": "Роман",
      "uk": "Роман",
      "pl": "Romance"
    }
  },
  {
    "id": 878,
    "name": "Science Fiction",
    "translations": {
      "en": "Science Fiction",
      "ru": "Научно-популярный",
      "uk": "Науково-популярний",
      "pl": "Science Fiction"
    }
  },
  {
    "id": 10770,
    "name": "TV Movie",
    "translations": {
      "en": "TV Movie",
      "ru": "Телесериал",
      "uk": "Телесеріал",
      "pl": "TV Movie"
    }
  },
  {
    "id": 53,
    "name": "Thriller",
    "translations": {
      "en": "Thriller",
      "ru": "Триллер",
      "uk": "Триллер",
      "pl": "Thriller"
    }
  },
  {
    "id": 10752,
    "name": "War",
    "translations": {
      "en": "War",
      "ru": "Военный",
      "uk": "Військовий",
      "pl": "War"
    }
  },
  {
    "id": 37,
    "name": "Western",
    "translations": {
      "en": "Western",
      "ru": "Вестерн",
      "uk": "Вестерн",
      "pl": "Western"
    }
  }
]
JSON;
    }
}
