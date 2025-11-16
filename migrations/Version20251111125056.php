<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251111125056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ciine (id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, author VARCHAR(255) NOT NULL, featured BOOLEAN NOT NULL, duration INT DEFAULT NULL, duration_text VARCHAR(255) NOT NULL, page INT DEFAULT NULL, filesize INT DEFAULT NULL, cast_url VARCHAR(2048) NOT NULL, download_url VARCHAR(2048) NOT NULL, scraped_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, marking VARCHAR(32) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE show ADD author VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE show ADD asciinama_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE show ADD input_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE show ADD marking VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE show ALTER ascii_cast DROP NOT NULL');
        $this->addSql('ALTER TABLE show ALTER file_size DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ciine');
        $this->addSql('ALTER TABLE show DROP author');
        $this->addSql('ALTER TABLE show DROP asciinama_id');
        $this->addSql('ALTER TABLE show DROP input_count');
        $this->addSql('ALTER TABLE show DROP marking');
        $this->addSql('ALTER TABLE show ALTER ascii_cast SET NOT NULL');
        $this->addSql('ALTER TABLE show ALTER file_size SET NOT NULL');
    }
}
