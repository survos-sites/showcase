<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928075736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove obsolete search index metadata and workflow pending steps';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE index_info');
        $this->addSql('ALTER TABLE component DROP pending_steps');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE index_info (index_name VARCHAR(255) NOT NULL, locale VARCHAR(255) DEFAULT NULL, last_indexed TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, document_count INT NOT NULL, settings JSONB NOT NULL, task_id VARCHAR(255) DEFAULT NULL, primary_key VARCHAR(255) NOT NULL, batch_id VARCHAR(255) DEFAULT NULL, status VARCHAR(20) DEFAULT NULL, label VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, aggregator VARCHAR(255) DEFAULT NULL, institution VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, PRIMARY KEY (index_name))');
        $this->addSql('ALTER TABLE component ADD pending_steps JSON DEFAULT \'{}\' NOT NULL');
    }
}
