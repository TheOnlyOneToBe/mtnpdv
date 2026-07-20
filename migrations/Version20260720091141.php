<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720091141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attribution_pdv (id INT AUTO_INCREMENT NOT NULL, date_attribution DATETIME NOT NULL, date_retrait DATETIME DEFAULT NULL, actif TINYINT NOT NULL, agent_id INT NOT NULL, point_vente_id INT NOT NULL, INDEX IDX_ATTRIBUTION_AGENT (agent_id), INDEX IDX_ATTRIBUTION_PDV (point_vente_id), UNIQUE INDEX UNIQ_ATTRIBUTION_AGENT_PDV_ACTIF (agent_id, point_vente_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attribution_pdv ADD CONSTRAINT FK_BFAABD503414710B FOREIGN KEY (agent_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE attribution_pdv ADD CONSTRAINT FK_BFAABD50EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE point_vente CHANGE solde_cash solde_cash NUMERIC(10, 2) DEFAULT 0 NOT NULL, CHANGE solde_flotte solde_flotte NUMERIC(10, 2) DEFAULT 0 NOT NULL, CHANGE seuil_min_cash seuil_min_cash NUMERIC(10, 2) DEFAULT 0 NOT NULL, CHANGE seuil_min_flotte seuil_min_flotte NUMERIC(10, 2) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attribution_pdv DROP FOREIGN KEY FK_BFAABD503414710B');
        $this->addSql('ALTER TABLE attribution_pdv DROP FOREIGN KEY FK_BFAABD50EFA24D68');
        $this->addSql('DROP TABLE attribution_pdv');
        $this->addSql('ALTER TABLE point_vente CHANGE solde_cash solde_cash NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE solde_flotte solde_flotte NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE seuil_min_cash seuil_min_cash NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE seuil_min_flotte seuil_min_flotte NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
    }
}
