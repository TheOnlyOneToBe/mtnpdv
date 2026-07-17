<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Insère les trois rôles de base du système (README : matrice des permissions).
 */
final class Version20260701000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed des rôles ADMIN, AGENT et GERANT';
    }

    public function up(Schema $schema): void
    {
        // $this->addSql("INSERT INTO role (code_role, libelle) VALUES ('ADMIN', 'Administrateur')");
        // $this->addSql("INSERT INTO role (code_role, libelle) VALUES ('AGENT', 'Agent commercial')");
        // $this->addSql("INSERT INTO role (code_role, libelle) VALUES ('GERANT', 'Gérant du kiosque')");
    }

    public function down(Schema $schema): void
    {
        // $this->addSql("DELETE FROM role WHERE code_role IN ('ADMIN', 'AGENT', 'GERANT')");
    }
}
