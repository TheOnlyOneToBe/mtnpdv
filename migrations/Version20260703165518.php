<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703165518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_visite (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, motif VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, date_demandee DATETIME NOT NULL, date_creation DATETIME NOT NULL, date_effectuee DATETIME DEFAULT NULL, date_validee DATETIME DEFAULT NULL, statut VARCHAR(50) NOT NULL, motif_rejet CLOB DEFAULT NULL, point_vente_id INTEGER DEFAULT NULL, agent_id INTEGER DEFAULT NULL, admin_id INTEGER NOT NULL, transaction_id INTEGER DEFAULT NULL, CONSTRAINT FK_F2D8CA54EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F2D8CA543414710B FOREIGN KEY (agent_id) REFERENCES utilisateur (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F2D8CA54642B8210 FOREIGN KEY (admin_id) REFERENCES utilisateur (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F2D8CA542FC0CB0F FOREIGN KEY (transaction_id) REFERENCES "transaction" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F2D8CA54642B8210 ON demande_visite (admin_id)');
        $this->addSql('CREATE INDEX IDX_F2D8CA542FC0CB0F ON demande_visite (transaction_id)');
        $this->addSql('CREATE INDEX IDX_DEMANDE_VISITE_PDV ON demande_visite (point_vente_id)');
        $this->addSql('CREATE INDEX IDX_DEMANDE_VISITE_AGENT ON demande_visite (agent_id)');
        $this->addSql('CREATE INDEX IDX_DEMANDE_VISITE_STATUT ON demande_visite (statut)');
        $this->addSql('CREATE TABLE notification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, titre VARCHAR(500) NOT NULL, message CLOB NOT NULL, lien VARCHAR(255) DEFAULT NULL, lu BOOLEAN NOT NULL, date_creation DATETIME NOT NULL, date_lecture DATETIME DEFAULT NULL, utilisateur_id INTEGER NOT NULL, CONSTRAINT FK_BF5476CAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BF5476CAFB88E14F ON notification (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CAFB88E14FA8FEB3E7 ON notification (utilisateur_id, lu)');
        $this->addSql('CREATE INDEX IDX_BF5476CA94DBECD2 ON notification (date_creation)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__flux_ravitaillement AS SELECT id, facture_uniq, date_creation, montant_total, statut_flux, utilisateur_id, point_vente_id FROM flux_ravitaillement');
        $this->addSql('DROP TABLE flux_ravitaillement');
        $this->addSql('CREATE TABLE flux_ravitaillement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, facture_uniq VARCHAR(100) NOT NULL, date_creation DATETIME NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, statut_flux VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, utilisateur_id INTEGER DEFAULT NULL, point_vente_id INTEGER DEFAULT NULL, FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO flux_ravitaillement (id, facture_uniq, date_creation, montant_total, statut_flux, utilisateur_id, point_vente_id) SELECT id, facture_uniq, date_creation, montant_total, statut_flux, utilisateur_id, point_vente_id FROM __temp__flux_ravitaillement');
        $this->addSql('DROP TABLE __temp__flux_ravitaillement');
        $this->addSql('CREATE INDEX IDX_3B34F787FB88E14F ON flux_ravitaillement (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_3B34F787EFA24D68 ON flux_ravitaillement (point_vente_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FLUX_RAVITAILLEMENT_FACTURE ON flux_ravitaillement (facture_uniq)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__point_vente AS SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id FROM point_vente');
        $this->addSql('DROP TABLE point_vente');
        $this->addSql('CREATE TABLE point_vente (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_pdv VARCHAR(255) NOT NULL, code_ref VARCHAR(100) NOT NULL, ville VARCHAR(100) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, statut_actuel VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, categorie_pdv_id INTEGER DEFAULT NULL, gerant_id INTEGER DEFAULT NULL, FOREIGN KEY (categorie_pdv_id) REFERENCES categorie_pdv (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (gerant_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO point_vente (id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id) SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id FROM __temp__point_vente');
        $this->addSql('DROP TABLE __temp__point_vente');
        $this->addSql('CREATE INDEX IDX_2BBFAADF872034B4 ON point_vente (categorie_pdv_id)');
        $this->addSql('CREATE INDEX IDX_2BBFAADFA500A924 ON point_vente (gerant_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_POINT_VENTE_CODE_REF ON point_vente (code_ref)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__produit AS SELECT id, nom_prod, type_pro, prix_unitaire, statut_prod, code_barre, categorie_id FROM produit');
        $this->addSql('DROP TABLE produit');
        $this->addSql('CREATE TABLE produit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_prod VARCHAR(255) NOT NULL, type_pro VARCHAR(50) NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, statut_prod SMALLINT DEFAULT 1 NOT NULL, code_barre VARCHAR(100) DEFAULT NULL, categorie_id INTEGER DEFAULT NULL, FOREIGN KEY (categorie_id) REFERENCES categorie_prod (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO produit (id, nom_prod, type_pro, prix_unitaire, statut_prod, code_barre, categorie_id) SELECT id, nom_prod, type_pro, prix_unitaire, statut_prod, code_barre, categorie_id FROM __temp__produit');
        $this->addSql('DROP TABLE __temp__produit');
        $this->addSql('CREATE INDEX IDX_29A5EC27BCF5E72D ON produit (categorie_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PRODUIT_CODE_BARRE ON produit (code_barre)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__role AS SELECT id, code_role, libelle FROM role');
        $this->addSql('DROP TABLE role');
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code_role VARCHAR(100) NOT NULL, libelle VARCHAR(100) NOT NULL)');
        $this->addSql('INSERT INTO role (id, code_role, libelle) SELECT id, code_role, libelle FROM __temp__role');
        $this->addSql('DROP TABLE __temp__role');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ROLE_CODE ON role (code_role)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__transaction AS SELECT id, date_transac, commentaire_rapport, photo_preuve_url, latitude_capture, longitude_capture, type_enum, statut, montant, point_vente_id, utilisateur_id FROM "transaction"');
        $this->addSql('DROP TABLE "transaction"');
        $this->addSql('CREATE TABLE "transaction" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_transac DATETIME NOT NULL, commentaire_rapport CLOB DEFAULT NULL, photo_preuve_url VARCHAR(255) DEFAULT NULL, latitude_capture NUMERIC(10, 8) NOT NULL, longitude_capture NUMERIC(11, 8) NOT NULL, type_enum VARCHAR(50) NOT NULL, statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, montant NUMERIC(10, 2) NOT NULL, point_vente_id INTEGER DEFAULT NULL, utilisateur_id INTEGER DEFAULT NULL, FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "transaction" (id, date_transac, commentaire_rapport, photo_preuve_url, latitude_capture, longitude_capture, type_enum, statut, montant, point_vente_id, utilisateur_id) SELECT id, date_transac, commentaire_rapport, photo_preuve_url, latitude_capture, longitude_capture, type_enum, statut, montant, point_vente_id, utilisateur_id FROM __temp__transaction');
        $this->addSql('DROP TABLE __temp__transaction');
        $this->addSql('CREATE INDEX IDX_723705D1EFA24D68 ON "transaction" (point_vente_id)');
        $this->addSql('CREATE INDEX IDX_723705D1FB88E14F ON "transaction" (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_TRANSACTION_DATE ON "transaction" (date_transac)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__utilisateur AS SELECT id, nom_ut, prenom_ut, email, mot_pass, telephone, date_creation, statut, photo_profil_url, date_photo_update FROM utilisateur');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('CREATE TABLE utilisateur (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_ut VARCHAR(255) NOT NULL, prenom_ut VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mot_pass VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, statut SMALLINT DEFAULT 1 NOT NULL, photo_profil_url VARCHAR(255) DEFAULT NULL, date_photo_update DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO utilisateur (id, nom_ut, prenom_ut, email, mot_pass, telephone, date_creation, statut, photo_profil_url, date_photo_update) SELECT id, nom_ut, prenom_ut, email, mot_pass, telephone, date_creation, statut, photo_profil_url, date_photo_update FROM __temp__utilisateur');
        $this->addSql('DROP TABLE __temp__utilisateur');
        $this->addSql('CREATE INDEX IDX_UTILISATEUR_TELEPHONE ON utilisateur (telephone)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_UTILISATEUR_EMAIL ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE demande_visite');
        $this->addSql('DROP TABLE notification');
        $this->addSql('CREATE TEMPORARY TABLE __temp__flux_ravitaillement AS SELECT id, facture_uniq, date_creation, montant_total, statut_flux, utilisateur_id, point_vente_id FROM flux_ravitaillement');
        $this->addSql('DROP TABLE flux_ravitaillement');
        $this->addSql('CREATE TABLE flux_ravitaillement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, facture_uniq VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, statut_flux VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, utilisateur_id INTEGER DEFAULT NULL, point_vente_id INTEGER DEFAULT NULL, CONSTRAINT FK_3B34F787FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3B34F787EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO flux_ravitaillement (id, facture_uniq, date_creation, montant_total, statut_flux, utilisateur_id, point_vente_id) SELECT id, facture_uniq, date_creation, montant_total, statut_flux, utilisateur_id, point_vente_id FROM __temp__flux_ravitaillement');
        $this->addSql('DROP TABLE __temp__flux_ravitaillement');
        $this->addSql('CREATE INDEX IDX_3B34F787FB88E14F ON flux_ravitaillement (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_3B34F787EFA24D68 ON flux_ravitaillement (point_vente_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__point_vente AS SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id FROM point_vente');
        $this->addSql('DROP TABLE point_vente');
        $this->addSql('CREATE TABLE point_vente (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_pdv VARCHAR(255) NOT NULL, code_ref VARCHAR(100) NOT NULL, ville VARCHAR(100) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, statut_actuel VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, categorie_pdv_id INTEGER DEFAULT NULL, gerant_id INTEGER DEFAULT NULL, CONSTRAINT FK_2BBFAADF872034B4 FOREIGN KEY (categorie_pdv_id) REFERENCES categorie_pdv (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2BBFAADFA500A924 FOREIGN KEY (gerant_id) REFERENCES utilisateur (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO point_vente (id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id) SELECT id, nom_pdv, code_ref, ville, adresse, date_creation, statut_actuel, telephone, latitude, longitude, categorie_pdv_id, gerant_id FROM __temp__point_vente');
        $this->addSql('DROP TABLE __temp__point_vente');
        $this->addSql('CREATE INDEX IDX_2BBFAADF872034B4 ON point_vente (categorie_pdv_id)');
        $this->addSql('CREATE INDEX IDX_2BBFAADFA500A924 ON point_vente (gerant_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__produit AS SELECT id, nom_prod, type_pro, prix_unitaire, statut_prod, code_barre, categorie_id FROM produit');
        $this->addSql('DROP TABLE produit');
        $this->addSql('CREATE TABLE produit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_prod VARCHAR(255) NOT NULL, type_pro VARCHAR(50) NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, statut_prod SMALLINT DEFAULT 1 NOT NULL, code_barre VARCHAR(100) DEFAULT NULL, categorie_id INTEGER DEFAULT NULL, CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_prod (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO produit (id, nom_prod, type_pro, prix_unitaire, statut_prod, code_barre, categorie_id) SELECT id, nom_prod, type_pro, prix_unitaire, statut_prod, code_barre, categorie_id FROM __temp__produit');
        $this->addSql('DROP TABLE __temp__produit');
        $this->addSql('CREATE INDEX IDX_29A5EC27BCF5E72D ON produit (categorie_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__role AS SELECT id, code_role, libelle FROM role');
        $this->addSql('DROP TABLE role');
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code_role VARCHAR(100) NOT NULL, libelle VARCHAR(100) NOT NULL)');
        $this->addSql('INSERT INTO role (id, code_role, libelle) SELECT id, code_role, libelle FROM __temp__role');
        $this->addSql('DROP TABLE __temp__role');
        $this->addSql('CREATE TEMPORARY TABLE __temp__transaction AS SELECT id, date_transac, commentaire_rapport, photo_preuve_url, latitude_capture, longitude_capture, type_enum, statut, montant, point_vente_id, utilisateur_id FROM "transaction"');
        $this->addSql('DROP TABLE "transaction"');
        $this->addSql('CREATE TABLE "transaction" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_transac DATETIME NOT NULL, commentaire_rapport CLOB DEFAULT NULL, photo_preuve_url VARCHAR(255) DEFAULT NULL, latitude_capture NUMERIC(10, 8) NOT NULL, longitude_capture NUMERIC(11, 8) NOT NULL, type_enum VARCHAR(50) NOT NULL, statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, montant NUMERIC(10, 2) NOT NULL, point_vente_id INTEGER DEFAULT NULL, utilisateur_id INTEGER DEFAULT NULL, CONSTRAINT FK_723705D1EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_723705D1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "transaction" (id, date_transac, commentaire_rapport, photo_preuve_url, latitude_capture, longitude_capture, type_enum, statut, montant, point_vente_id, utilisateur_id) SELECT id, date_transac, commentaire_rapport, photo_preuve_url, latitude_capture, longitude_capture, type_enum, statut, montant, point_vente_id, utilisateur_id FROM __temp__transaction');
        $this->addSql('DROP TABLE __temp__transaction');
        $this->addSql('CREATE INDEX IDX_723705D1EFA24D68 ON "transaction" (point_vente_id)');
        $this->addSql('CREATE INDEX IDX_723705D1FB88E14F ON "transaction" (utilisateur_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__utilisateur AS SELECT id, nom_ut, prenom_ut, email, mot_pass, telephone, date_creation, statut, photo_profil_url, date_photo_update FROM utilisateur');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('CREATE TABLE utilisateur (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_ut VARCHAR(255) NOT NULL, prenom_ut VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mot_pass VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, statut SMALLINT DEFAULT 1 NOT NULL, photo_profil_url VARCHAR(255) DEFAULT NULL, date_photo_update DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO utilisateur (id, nom_ut, prenom_ut, email, mot_pass, telephone, date_creation, statut, photo_profil_url, date_photo_update) SELECT id, nom_ut, prenom_ut, email, mot_pass, telephone, date_creation, statut, photo_profil_url, date_photo_update FROM __temp__utilisateur');
        $this->addSql('DROP TABLE __temp__utilisateur');
    }
}
