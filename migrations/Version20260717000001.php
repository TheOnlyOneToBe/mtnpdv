<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs solde et seuil pour point_vente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__point_vente AS SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id FROM point_vente');
        $this->addSql('DROP TABLE point_vente');
        $this->addSql('CREATE TABLE point_vente (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_pdv VARCHAR(255) NOT NULL, code_ref VARCHAR(100) NOT NULL, ville VARCHAR(100) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, statut_actuel VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, categorie_pdv_id INTEGER DEFAULT NULL, gerant_id INTEGER DEFAULT NULL, solde_cash NUMERIC(10, 2) DEFAULT 0 NOT NULL, solde_flotte NUMERIC(10, 2) DEFAULT 0 NOT NULL, seuil_min_cash NUMERIC(10, 2) DEFAULT 0 NOT NULL, seuil_min_flotte NUMERIC(10, 2) DEFAULT 0 NOT NULL, FOREIGN KEY (categorie_pdv_id) REFERENCES categorie_pdv (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (gerant_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO point_vente (id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id, solde_cash, solde_flotte, seuil_min_cash, seuil_min_flotte) SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id, 0, 0, 0, 0 FROM __temp__point_vente');
        $this->addSql('DROP TABLE __temp__point_vente');
        $this->addSql('CREATE INDEX IDX_2BBFAADF872034B4 ON point_vente (categorie_pdv_id)');
        $this->addSql('CREATE INDEX IDX_2BBFAADFA500A924 ON point_vente (gerant_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_POINT_VENTE_CODE_REF ON point_vente (code_ref)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__point_vente AS SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id FROM point_vente');
        $this->addSql('DROP TABLE point_vente');
        $this->addSql('CREATE TABLE point_vente (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_pdv VARCHAR(255) NOT NULL, code_ref VARCHAR(100) NOT NULL, ville VARCHAR(100) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, statut_actuel VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, categorie_pdv_id INTEGER DEFAULT NULL, gerant_id INTEGER DEFAULT NULL, FOREIGN KEY (categorie_pdv_id) REFERENCES categorie_pdv (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (gerant_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO point_vente (id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id) SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id FROM __temp__point_vente');
        $this->addSql('DROP TABLE __temp__point_vente');
        $this->addSql('CREATE INDEX IDX_2BBFAADF872034B4 ON point_vente (categorie_pdv_id)');
        $this->addSql('CREATE INDEX IDX_2BBFAADFA500A924 ON point_vente (gerant_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_POINT_VENTE_CODE_REF ON point_vente (code_ref)');
    }
}
