<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to fix key length issue with facture_uniq column in flux_ravitaillement table
 *
 * MySQL has a limit on key length (767 bytes for InnoDB). VARCHAR(255) with utf8mb4
 * uses 1020 bytes (255 * 4), which exceeds this limit.
 * This migration reduces the column length to 100 (400 bytes), which is sufficient
 * for invoice reference numbers.
 */
final class Version20260702000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix key length issue with facture_uniq column in flux_ravitaillement table';
    }

    public function up(Schema $schema): void
    {
        // Only apply to MySQL platforms
        if (!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return;
        }

        // Drop the long unique index
        $this->addSql('ALTER TABLE flux_ravitaillement DROP INDEX UNIQ_FLUX_RAVITAILLEMENT_FACTURE');

        // Reduce the length of facture_uniq from VARCHAR(255) to VARCHAR(100)
        $this->addSql('ALTER TABLE flux_ravitaillement MODIFY facture_uniq VARCHAR(100) NOT NULL');

        // Recreate the unique index with shorter length
        $this->addSql('ALTER TABLE flux_ravitaillement ADD CONSTRAINT UNIQ_FLUX_RAVITAILLEMENT_FACTURE UNIQUE (facture_uniq)');
    }

    public function down(Schema $schema): void
    {
        // Only apply to MySQL platforms
        if (!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return;
        }

        // Drop the short unique index
        $this->addSql('ALTER TABLE flux_ravitaillement DROP INDEX UNIQ_FLUX_RAVITAILLEMENT_FACTURE');

        // Restore the original length
        $this->addSql('ALTER TABLE flux_ravitaillement MODIFY facture_uniq VARCHAR(255) NOT NULL');

        // Recreate the original index
        $this->addSql('ALTER TABLE flux_ravitaillement ADD CONSTRAINT UNIQ_FLUX_RAVITAILLEMENT_FACTURE UNIQUE (facture_uniq)');
    }
}
