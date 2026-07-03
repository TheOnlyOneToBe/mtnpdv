<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701220614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add photo fields to utilisateur entity for VichUploaderBundle';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform;

        $this->addSql('ALTER TABLE utilisateur ADD photo_profil_url VARCHAR(255) DEFAULT NULL');

        if ($isSqlite) {
            $this->addSql('ALTER TABLE utilisateur ADD date_photo_update DATETIME DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE utilisateur ADD date_photo_update DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP photo_profil_url');
        $this->addSql('ALTER TABLE utilisateur DROP date_photo_update');
    }
}
