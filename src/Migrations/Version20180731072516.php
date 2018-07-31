<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180731072516 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $pass = '';
        $roles = '["ROLE_USER","ROLE_ADMIN"]';
        $this->addSql("INSERT INTO users_profiles (id, first_name, last_name, birth_date, about, public_email) VALUES (1, 'Support', 'Support', NULL, NULL, 'svbackend22@gmail.com')");
        $this->addSql("INSERT INTO users (id, profile_id, email, is_email_confirmed, username, password, roles) VALUES (1, 1, 'svbackend22@gmail.com', 1, 'support', '$pass', '$roles')");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DELETE FROM users WHERE id = 1');
        $this->addSql('DELETE FROM users_profiles WHERE id = 1');
    }
}
