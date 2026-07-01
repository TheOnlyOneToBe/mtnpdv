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
        $this->addSql('CREATE TABLE categorie_pdv (id INT AUTO_INCREMENT NOT NULL, libelle_catpdv VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categorie_prod (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, type_cat VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, code_role VARCHAR(100) NOT NULL, libelle VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_ROLE_CODE (code_role), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom_ut VARCHAR(255) NOT NULL, prenom_ut VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mot_pass VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, statut SMALLINT DEFAULT 1 NOT NULL, INDEX IDX_UTILISATEUR_TELEPHONE (telephone), UNIQUE INDEX UNIQ_UTILISATEUR_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur_role (utilisateur_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_9EE8E650FB88E14F (utilisateur_id), INDEX IDX_9EE8E650D60322AC (role_id), PRIMARY KEY (utilisateur_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE point_vente (id INT AUTO_INCREMENT NOT NULL, nom_pdv VARCHAR(255) NOT NULL, code_ref VARCHAR(100) NOT NULL, ville VARCHAR(100) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, statut_actuel VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, categorie_pdv_id INT DEFAULT NULL, gerant_id INT DEFAULT NULL, INDEX IDX_2BBFAADF872034B4 (categorie_pdv_id), INDEX IDX_2BBFAADFA500A924 (gerant_id), UNIQUE INDEX UNIQ_POINT_VENTE_CODE_REF (code_ref), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom_prod VARCHAR(255) NOT NULL, type_pro VARCHAR(50) NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, statut_prod SMALLINT DEFAULT 1 NOT NULL, code_barre VARCHAR(100) DEFAULT NULL, categorie_id INT DEFAULT NULL, INDEX IDX_29A5EC27BCF5E72D (categorie_id), UNIQUE INDEX UNIQ_PRODUIT_CODE_BARRE (code_barre), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE flux_ravitaillement (id INT AUTO_INCREMENT NOT NULL, facture_uniq VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, statut_flux VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, utilisateur_id INT DEFAULT NULL, point_vente_id INT DEFAULT NULL, INDEX IDX_3B34F787FB88E14F (utilisateur_id), INDEX IDX_3B34F787EFA24D68 (point_vente_id), UNIQUE INDEX UNIQ_FLUX_RAVITAILLEMENT_FACTURE (facture_uniq), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE flux_produit (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, sous_total NUMERIC(10, 2) NOT NULL, prix_unitaire_flux NUMERIC(10, 2) NOT NULL, flux_ravitaillement_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, INDEX IDX_85A0F65AF8724BC1 (flux_ravitaillement_id), INDEX IDX_85A0F65AF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `transaction` (id INT AUTO_INCREMENT NOT NULL, date_transac DATETIME NOT NULL, commentaire_rapport LONGTEXT DEFAULT NULL, photo_preuve_url VARCHAR(255) DEFAULT NULL, latitude_capture NUMERIC(10, 8) NOT NULL, longitude_capture NUMERIC(11, 8) NOT NULL, type_enum VARCHAR(50) NOT NULL, statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, montant NUMERIC(10, 2) NOT NULL, point_vente_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_723705D1EFA24D68 (point_vente_id), INDEX IDX_723705D1FB88E14F (utilisateur_id), INDEX IDX_TRANSACTION_DATE (date_transac), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_role ADD CONSTRAINT FK_9EE8E650FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_role ADD CONSTRAINT FK_9EE8E650D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE point_vente ADD CONSTRAINT FK_2BBFAADF872034B4 FOREIGN KEY (categorie_pdv_id) REFERENCES categorie_pdv (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE point_vente ADD CONSTRAINT FK_2BBFAADFA500A924 FOREIGN KEY (gerant_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_prod (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE flux_ravitaillement ADD CONSTRAINT FK_3B34F787FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE flux_ravitaillement ADD CONSTRAINT FK_3B34F787EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE flux_produit ADD CONSTRAINT FK_85A0F65AF8724BC1 FOREIGN KEY (flux_ravitaillement_id) REFERENCES flux_ravitaillement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE flux_produit ADD CONSTRAINT FK_85A0F65AF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_723705D1EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_723705D1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
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
