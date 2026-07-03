<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Schéma initial : catégories, rôles, utilisateurs, points de vente,
 * produits, flux de ravitaillement et transactions.
 */
final class Version20260701000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du schéma initial de la base projet_licence';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform;
        $charset = $isSqlite ? '' : ' DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB';

        $this->addSql('CREATE TABLE categorie_pdv (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, libelle_catpdv VARCHAR(100) NOT NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE categorie_prod (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, type_cat VARCHAR(50) NOT NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code_role VARCHAR(100) NOT NULL UNIQUE, libelle VARCHAR(100) NOT NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE utilisateur (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_ut VARCHAR(255) NOT NULL, prenom_ut VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL UNIQUE, mot_pass VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, statut SMALLINT DEFAULT 1 NOT NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE utilisateur_role (utilisateur_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY (utilisateur_id, role_id), FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE, FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE point_vente (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_pdv VARCHAR(255) NOT NULL, code_ref VARCHAR(100) NOT NULL UNIQUE, ville VARCHAR(100) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, statut_actuel VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, categorie_pdv_id INT DEFAULT NULL, gerant_id INT DEFAULT NULL, FOREIGN KEY (categorie_pdv_id) REFERENCES categorie_pdv (id) ON DELETE SET NULL, FOREIGN KEY (gerant_id) REFERENCES utilisateur (id) ON DELETE SET NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE produit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_prod VARCHAR(255) NOT NULL, type_pro VARCHAR(50) NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, statut_prod SMALLINT DEFAULT 1 NOT NULL, code_barre VARCHAR(100) UNIQUE DEFAULT NULL, categorie_id INT DEFAULT NULL, FOREIGN KEY (categorie_id) REFERENCES categorie_prod (id) ON DELETE SET NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE flux_ravitaillement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, facture_uniq VARCHAR(255) NOT NULL UNIQUE, date_creation DATETIME NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, statut_flux VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, utilisateur_id INT DEFAULT NULL, point_vente_id INT DEFAULT NULL, FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL, FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE flux_produit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantite INT NOT NULL, sous_total NUMERIC(10, 2) NOT NULL, prix_unitaire_flux NUMERIC(10, 2) NOT NULL, flux_ravitaillement_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, FOREIGN KEY (flux_ravitaillement_id) REFERENCES flux_ravitaillement (id) ON DELETE CASCADE, FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE SET NULL)' . ($platform !== 'sqlite' ? $charset : ''));
        $this->addSql('CREATE TABLE `transaction` (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_transac DATETIME NOT NULL, commentaire_rapport LONGTEXT DEFAULT NULL, photo_preuve_url VARCHAR(255) DEFAULT NULL, latitude_capture NUMERIC(10, 8) NOT NULL, longitude_capture NUMERIC(11, 8) NOT NULL, type_enum VARCHAR(50) NOT NULL, statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, montant NUMERIC(10, 2) NOT NULL, point_vente_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL, FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL)' . ($platform !== 'sqlite' ? $charset : ''));

        if (!$isSqlite) {
            $this->addSql('CREATE INDEX IDX_UTILISATEUR_TELEPHONE ON utilisateur (telephone)');
            $this->addSql('CREATE INDEX IDX_UTILISATEUR_EMAIL ON utilisateur (email)');
            $this->addSql('CREATE INDEX IDX_2BBFAADF872034B4 ON point_vente (categorie_pdv_id)');
            $this->addSql('CREATE INDEX IDX_2BBFAADFA500A924 ON point_vente (gerant_id)');
            $this->addSql('CREATE INDEX IDX_29A5EC27BCF5E72D ON produit (categorie_id)');
            $this->addSql('CREATE INDEX IDX_3B34F787FB88E14F ON flux_ravitaillement (utilisateur_id)');
            $this->addSql('CREATE INDEX IDX_3B34F787EFA24D68 ON flux_ravitaillement (point_vente_id)');
            $this->addSql('CREATE INDEX IDX_85A0F65AF8724BC1 ON flux_produit (flux_ravitaillement_id)');
            $this->addSql('CREATE INDEX IDX_85A0F65AF347EFB ON flux_produit (produit_id)');
            $this->addSql('CREATE INDEX IDX_723705D1EFA24D68 ON `transaction` (point_vente_id)');
            $this->addSql('CREATE INDEX IDX_723705D1FB88E14F ON `transaction` (utilisateur_id)');
            $this->addSql('CREATE INDEX IDX_TRANSACTION_DATE ON `transaction` (date_transac)');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isMysql = !($platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform);
        if ($isMysql) {
            $this->addSql('ALTER TABLE `transaction` DROP FOREIGN KEY FK_723705D1EFA24D68');
            $this->addSql('ALTER TABLE `transaction` DROP FOREIGN KEY FK_723705D1FB88E14F');
            $this->addSql('ALTER TABLE flux_produit DROP FOREIGN KEY FK_85A0F65AF8724BC1');
            $this->addSql('ALTER TABLE flux_produit DROP FOREIGN KEY FK_85A0F65AF347EFB');
            $this->addSql('ALTER TABLE flux_ravitaillement DROP FOREIGN KEY FK_3B34F787FB88E14F');
            $this->addSql('ALTER TABLE flux_ravitaillement DROP FOREIGN KEY FK_3B34F787EFA24D68');
            $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
            $this->addSql('ALTER TABLE point_vente DROP FOREIGN KEY FK_2BBFAADF872034B4');
            $this->addSql('ALTER TABLE point_vente DROP FOREIGN KEY FK_2BBFAADFA500A924');
            $this->addSql('ALTER TABLE utilisateur_role DROP FOREIGN KEY FK_9EE8E650FB88E14F');
            $this->addSql('ALTER TABLE utilisateur_role DROP FOREIGN KEY FK_9EE8E650D60322AC');
        }
        $this->addSql('DROP TABLE `transaction`');
        $this->addSql('DROP TABLE flux_produit');
        $this->addSql('DROP TABLE flux_ravitaillement');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE point_vente');
        $this->addSql('DROP TABLE utilisateur_role');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE categorie_pdv');
        $this->addSql('DROP TABLE categorie_prod');
    }
}
