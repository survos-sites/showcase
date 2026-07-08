<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

/**
 * Extract the Ciine feature out of showcase: drop the ciine cast-hosting tables.
 * The feature now lives in survos/ciine-bundle + the ciine.survos.com hub app.
 * Start-fresh: no data is preserved.
 */
final class Version20260708120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop ciine and show tables (Ciine feature extracted to ciine-bundle / ciine.survos.com)';
    }

    public function up(Schema $schema): void
    {
        // "show" is a reserved word in PostgreSQL and must be quoted.
        $this->addSql('DROP TABLE IF EXISTS ciine');
        $this->addSql('DROP TABLE IF EXISTS "show"');
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('The Ciine feature was extracted from showcase; recreating its tables is not supported.');
    }
}
