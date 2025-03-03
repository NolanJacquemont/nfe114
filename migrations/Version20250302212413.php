<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250302212413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__practitioners AS SELECT id, description, speciality FROM practitioners');
        $this->addSql('DROP TABLE practitioners');
        $this->addSql('CREATE TABLE practitioners (id INTEGER NOT NULL, description CLOB NOT NULL, speciality VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_D9CE0290BF396750 FOREIGN KEY (id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO practitioners (id, description, speciality) SELECT id, description, speciality FROM __temp__practitioners');
        $this->addSql('DROP TABLE __temp__practitioners');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__practitioners AS SELECT id, description, speciality FROM practitioners');
        $this->addSql('DROP TABLE practitioners');
        $this->addSql('CREATE TABLE practitioners (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, description CLOB NOT NULL, speciality VARCHAR(255) NOT NULL, CONSTRAINT FK_D9CE0290BF396750 FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO practitioners (id, description, speciality) SELECT id, description, speciality FROM __temp__practitioners');
        $this->addSql('DROP TABLE __temp__practitioners');
    }
}
