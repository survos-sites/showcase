<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250523130947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE show (code VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, ascii_cast TEXT NOT NULL, PRIMARY KEY(code))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project ADD local_dir VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project ADD marking VARCHAR(32) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE show
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project DROP local_dir
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project DROP marking
        SQL);
    }
}
