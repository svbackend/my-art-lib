<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180731072516 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE movies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE similar_movies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE movies_recommendations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE movies_translations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_watched_movies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_profiles_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_profiles_contacts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_confirmation_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_api_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE genres_translations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE genres_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE guests_watched_movies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE guest_sessions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE movies (id INT NOT NULL, original_title VARCHAR(100) NOT NULL, original_poster_url VARCHAR(255) DEFAULT NULL, imdb_id VARCHAR(20) DEFAULT NULL, runtime INT DEFAULT 0, budget INT DEFAULT 0, release_date DATE DEFAULT NULL, tmdb_id INT NOT NULL, tmdb_vote_average NUMERIC(10, 0) DEFAULT NULL, tmdb_vote_count INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C61EED3055BCC5E5 ON movies (tmdb_id)');
        $this->addSql('CREATE TABLE movies_genres (movie_id INT NOT NULL, genre_id INT NOT NULL, PRIMARY KEY(movie_id, genre_id))');
        $this->addSql('CREATE INDEX IDX_DF9737A28F93B6FC ON movies_genres (movie_id)');
        $this->addSql('CREATE INDEX IDX_DF9737A24296D31F ON movies_genres (genre_id)');
        $this->addSql('CREATE TABLE similar_movies (id INT NOT NULL, original_movie_id INT NOT NULL, similar_movie_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9F40C88DACCC51ED ON similar_movies (original_movie_id)');
        $this->addSql('CREATE INDEX IDX_9F40C88D95FCEB10 ON similar_movies (similar_movie_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_SimilarMovie_original_movie_id_similar_movie_id ON similar_movies (original_movie_id, similar_movie_id)');
        $this->addSql('CREATE TABLE movies_recommendations (id INT NOT NULL, original_movie_id INT NOT NULL, recommended_movie_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1FCF2901ACCC51ED ON movies_recommendations (original_movie_id)');
        $this->addSql('CREATE INDEX IDX_1FCF29012431F8 ON movies_recommendations (recommended_movie_id)');
        $this->addSql('CREATE INDEX IDX_1FCF2901A76ED395 ON movies_recommendations (user_id)');
        $this->addSql('CREATE UNIQUE INDEX MovieRecommendation_original_movie_recommended_movie_user ON movies_recommendations (original_movie_id, recommended_movie_id, user_id)');
        $this->addSql('CREATE TABLE movies_translations (id INT NOT NULL, movie_id INT NOT NULL, locale VARCHAR(5) NOT NULL, title VARCHAR(100) NOT NULL, poster_url VARCHAR(255) DEFAULT NULL, overview TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C48EC62C8F93B6FC ON movies_translations (movie_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_MovieTranslations_locale_movie_id ON movies_translations (locale, movie_id)');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, profile_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, is_email_confirmed INT DEFAULT 0 NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(64) NOT NULL, roles VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9CCFA12B8 ON users (profile_id)');
        $this->addSql('CREATE TABLE users_watched_movies (id INT NOT NULL, user_id INT NOT NULL, movie_id INT NOT NULL, vote NUMERIC(10, 0) DEFAULT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, watched_at DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3F9A4B7DA76ED395 ON users_watched_movies (user_id)');
        $this->addSql('CREATE INDEX IDX_3F9A4B7D8F93B6FC ON users_watched_movies (movie_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_UserWatchedMovie_user_id_movie_id ON users_watched_movies (user_id, movie_id)');
        $this->addSql('CREATE TABLE users_profiles (id INT NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, birth_date DATE DEFAULT NULL, about VARCHAR(255) DEFAULT NULL, public_email VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE users_profiles_contacts (id INT NOT NULL, profile_id INT NOT NULL, provider VARCHAR(30) NOT NULL, url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_52D26C5ECCFA12B8 ON users_profiles_contacts (profile_id)');
        $this->addSql('CREATE TABLE users_confirmation_tokens (id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(32) NOT NULL, type VARCHAR(256) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_173B44C45F37A13B ON users_confirmation_tokens (token)');
        $this->addSql('CREATE INDEX IDX_173B44C4A76ED395 ON users_confirmation_tokens (user_id)');
        $this->addSql('CREATE TABLE users_api_tokens (id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(256) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBC6F99A5F37A13B ON users_api_tokens (token)');
        $this->addSql('CREATE INDEX IDX_FBC6F99AA76ED395 ON users_api_tokens (user_id)');
        $this->addSql('CREATE TABLE genres_translations (id INT NOT NULL, genre_id INT NOT NULL, locale VARCHAR(5) NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E6A3DB174296D31F ON genres_translations (genre_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_GenreTranslations_locale_genre_id ON genres_translations (locale, genre_id)');
        $this->addSql('CREATE TABLE genres (id INT NOT NULL, tmdb_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE guests_watched_movies (id INT NOT NULL, guest_session_id INT NOT NULL, movie_id INT NOT NULL, vote NUMERIC(10, 0) DEFAULT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, watched_at DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_72F09C865C27BC4E ON guests_watched_movies (guest_session_id)');
        $this->addSql('CREATE INDEX IDX_72F09C868F93B6FC ON guests_watched_movies (movie_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_GuestWatchedMovie_guest_session_id_movie_id ON guests_watched_movies (guest_session_id, movie_id)');
        $this->addSql('CREATE TABLE guest_sessions (id INT NOT NULL, token VARCHAR(256) NOT NULL, expires_at DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E54A556C5F37A13B ON guest_sessions (token)');
        $this->addSql('ALTER TABLE movies_genres ADD CONSTRAINT FK_DF9737A28F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movies_genres ADD CONSTRAINT FK_DF9737A24296D31F FOREIGN KEY (genre_id) REFERENCES genres (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE similar_movies ADD CONSTRAINT FK_9F40C88DACCC51ED FOREIGN KEY (original_movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE similar_movies ADD CONSTRAINT FK_9F40C88D95FCEB10 FOREIGN KEY (similar_movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movies_recommendations ADD CONSTRAINT FK_1FCF2901ACCC51ED FOREIGN KEY (original_movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movies_recommendations ADD CONSTRAINT FK_1FCF29012431F8 FOREIGN KEY (recommended_movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movies_recommendations ADD CONSTRAINT FK_1FCF2901A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movies_translations ADD CONSTRAINT FK_C48EC62C8F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9CCFA12B8 FOREIGN KEY (profile_id) REFERENCES users_profiles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_watched_movies ADD CONSTRAINT FK_3F9A4B7DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_watched_movies ADD CONSTRAINT FK_3F9A4B7D8F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_profiles_contacts ADD CONSTRAINT FK_52D26C5ECCFA12B8 FOREIGN KEY (profile_id) REFERENCES users_profiles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_confirmation_tokens ADD CONSTRAINT FK_173B44C4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_api_tokens ADD CONSTRAINT FK_FBC6F99AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE genres_translations ADD CONSTRAINT FK_E6A3DB174296D31F FOREIGN KEY (genre_id) REFERENCES genres (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guests_watched_movies ADD CONSTRAINT FK_72F09C865C27BC4E FOREIGN KEY (guest_session_id) REFERENCES guest_sessions (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guests_watched_movies ADD CONSTRAINT FK_72F09C868F93B6FC FOREIGN KEY (movie_id) REFERENCES movies (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE movies_genres DROP CONSTRAINT FK_DF9737A28F93B6FC');
        $this->addSql('ALTER TABLE similar_movies DROP CONSTRAINT FK_9F40C88DACCC51ED');
        $this->addSql('ALTER TABLE similar_movies DROP CONSTRAINT FK_9F40C88D95FCEB10');
        $this->addSql('ALTER TABLE movies_recommendations DROP CONSTRAINT FK_1FCF2901ACCC51ED');
        $this->addSql('ALTER TABLE movies_recommendations DROP CONSTRAINT FK_1FCF29012431F8');
        $this->addSql('ALTER TABLE movies_translations DROP CONSTRAINT FK_C48EC62C8F93B6FC');
        $this->addSql('ALTER TABLE users_watched_movies DROP CONSTRAINT FK_3F9A4B7D8F93B6FC');
        $this->addSql('ALTER TABLE guests_watched_movies DROP CONSTRAINT FK_72F09C868F93B6FC');
        $this->addSql('ALTER TABLE movies_recommendations DROP CONSTRAINT FK_1FCF2901A76ED395');
        $this->addSql('ALTER TABLE users_watched_movies DROP CONSTRAINT FK_3F9A4B7DA76ED395');
        $this->addSql('ALTER TABLE users_confirmation_tokens DROP CONSTRAINT FK_173B44C4A76ED395');
        $this->addSql('ALTER TABLE users_api_tokens DROP CONSTRAINT FK_FBC6F99AA76ED395');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9CCFA12B8');
        $this->addSql('ALTER TABLE users_profiles_contacts DROP CONSTRAINT FK_52D26C5ECCFA12B8');
        $this->addSql('ALTER TABLE movies_genres DROP CONSTRAINT FK_DF9737A24296D31F');
        $this->addSql('ALTER TABLE genres_translations DROP CONSTRAINT FK_E6A3DB174296D31F');
        $this->addSql('ALTER TABLE guests_watched_movies DROP CONSTRAINT FK_72F09C865C27BC4E');
        $this->addSql('DROP SEQUENCE movies_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE similar_movies_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE movies_recommendations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE movies_translations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_watched_movies_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_profiles_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_profiles_contacts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_confirmation_tokens_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_api_tokens_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE genres_translations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE genres_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE guests_watched_movies_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE guest_sessions_id_seq CASCADE');
        $this->addSql('DROP TABLE movies');
        $this->addSql('DROP TABLE movies_genres');
        $this->addSql('DROP TABLE similar_movies');
        $this->addSql('DROP TABLE movies_recommendations');
        $this->addSql('DROP TABLE movies_translations');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_watched_movies');
        $this->addSql('DROP TABLE users_profiles');
        $this->addSql('DROP TABLE users_profiles_contacts');
        $this->addSql('DROP TABLE users_confirmation_tokens');
        $this->addSql('DROP TABLE users_api_tokens');
        $this->addSql('DROP TABLE genres_translations');
        $this->addSql('DROP TABLE genres');
        $this->addSql('DROP TABLE guests_watched_movies');
        $this->addSql('DROP TABLE guest_sessions');
    }
}
