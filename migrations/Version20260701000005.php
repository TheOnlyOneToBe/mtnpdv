<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717145421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_pdv (id INT AUTO_INCREMENT NOT NULL, libelle_catpdv VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categorie_prod (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, type_cat VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demande_visite (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, montant NUMERIC(10, 2) NOT NULL, motif VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_demandee DATETIME NOT NULL, date_creation DATETIME NOT NULL, date_effectuee DATETIME DEFAULT NULL, date_validee DATETIME DEFAULT NULL, statut VARCHAR(50) NOT NULL, motif_rejet LONGTEXT DEFAULT NULL, point_vente_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, createur_id INT NOT NULL, transaction_id INT DEFAULT NULL, INDEX IDX_F2D8CA5473A201E5 (createur_id), INDEX IDX_F2D8CA542FC0CB0F (transaction_id), INDEX IDX_DEMANDE_VISITE_PDV (point_vente_id), INDEX IDX_DEMANDE_VISITE_AGENT (agent_id), INDEX IDX_DEMANDE_VISITE_STATUT (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE flux_produit (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, sous_total NUMERIC(10, 2) NOT NULL, prix_unitaire_flux NUMERIC(10, 2) NOT NULL, flux_ravitaillement_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, INDEX IDX_85A0F65AF8724BC1 (flux_ravitaillement_id), INDEX IDX_85A0F65AF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE flux_ravitaillement (id INT AUTO_INCREMENT NOT NULL, facture_uniq VARCHAR(30) NOT NULL, date_creation DATETIME NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, statut_flux VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, utilisateur_id INT DEFAULT NULL, point_vente_id INT DEFAULT NULL, INDEX IDX_3B34F787FB88E14F (utilisateur_id), INDEX IDX_3B34F787EFA24D68 (point_vente_id), UNIQUE INDEX UNIQ_FLUX_RAVITAILLEMENT_FACTURE (facture_uniq), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, titre VARCHAR(500) NOT NULL, message LONGTEXT NOT NULL, lien VARCHAR(255) DEFAULT NULL, lu TINYINT NOT NULL, date_creation DATETIME NOT NULL, date_lecture DATETIME DEFAULT NULL, utilisateur_id INT NOT NULL, INDEX IDX_BF5476CAFB88E14F (utilisateur_id), INDEX IDX_BF5476CAFB88E14FA8FEB3E7 (utilisateur_id, lu), INDEX IDX_BF5476CA94DBECD2 (date_creation), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE point_vente (id INT AUTO_INCREMENT NOT NULL, nom_pdv VARCHAR(255) NOT NULL, code_ref VARCHAR(100) NOT NULL, ville VARCHAR(100) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, statut_actuel VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, solde_cash NUMERIC(10, 2) DEFAULT 0 NOT NULL, solde_flotte NUMERIC(10, 2) DEFAULT 0 NOT NULL, seuil_min_cash NUMERIC(10, 2) DEFAULT 0 NOT NULL, seuil_min_flotte NUMERIC(10, 2) DEFAULT 0 NOT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, categorie_pdv_id INT DEFAULT NULL, gerant_id INT DEFAULT NULL, INDEX IDX_2BBFAADF872034B4 (categorie_pdv_id), INDEX IDX_2BBFAADFA500A924 (gerant_id), UNIQUE INDEX UNIQ_POINT_VENTE_CODE_REF (code_ref), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom_prod VARCHAR(255) NOT NULL, type_pro VARCHAR(50) NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, statut_prod SMALLINT DEFAULT 1 NOT NULL, code_barre VARCHAR(100) DEFAULT NULL, categorie_id INT DEFAULT NULL, INDEX IDX_29A5EC27BCF5E72D (categorie_id), UNIQUE INDEX UNIQ_PRODUIT_CODE_BARRE (code_barre), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, code_role VARCHAR(100) NOT NULL, libelle VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_ROLE_CODE (code_role), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `transaction` (id INT AUTO_INCREMENT NOT NULL, date_transac DATETIME NOT NULL, commentaire_rapport LONGTEXT DEFAULT NULL, photo_preuve_url VARCHAR(255) DEFAULT NULL, latitude_capture NUMERIC(10, 8) NOT NULL, longitude_capture NUMERIC(11, 8) NOT NULL, type_enum VARCHAR(50) NOT NULL, statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, montant NUMERIC(10, 2) NOT NULL, type_probleme VARCHAR(100) DEFAULT NULL, point_vente_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_723705D1EFA24D68 (point_vente_id), INDEX IDX_723705D1FB88E14F (utilisateur_id), INDEX IDX_TRANSACTION_DATE (date_transac), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom_ut VARCHAR(255) NOT NULL, prenom_ut VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mot_pass VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, statut SMALLINT DEFAULT 1 NOT NULL, photo_profil_url VARCHAR(255) DEFAULT NULL, date_photo_update DATETIME DEFAULT NULL, INDEX IDX_UTILISATEUR_TELEPHONE (telephone), UNIQUE INDEX UNIQ_UTILISATEUR_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur_role (utilisateur_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_9EE8E650FB88E14F (utilisateur_id), INDEX IDX_9EE8E650D60322AC (role_id), PRIMARY KEY (utilisateur_id, role_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande_visite ADD CONSTRAINT FK_F2D8CA54EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_visite ADD CONSTRAINT FK_F2D8CA543414710B FOREIGN KEY (agent_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande_visite ADD CONSTRAINT FK_F2D8CA5473A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_visite ADD CONSTRAINT FK_F2D8CA542FC0CB0F FOREIGN KEY (transaction_id) REFERENCES `transaction` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE flux_produit ADD CONSTRAINT FK_85A0F65AF8724BC1 FOREIGN KEY (flux_ravitaillement_id) REFERENCES flux_ravitaillement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE flux_produit ADD CONSTRAINT FK_85A0F65AF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE flux_ravitaillement ADD CONSTRAINT FK_3B34F787FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE flux_ravitaillement ADD CONSTRAINT FK_3B34F787EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE point_vente ADD CONSTRAINT FK_2BBFAADF872034B4 FOREIGN KEY (categorie_pdv_id) REFERENCES categorie_pdv (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE point_vente ADD CONSTRAINT FK_2BBFAADFA500A924 FOREIGN KEY (gerant_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_prod (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_723705D1EFA24D68 FOREIGN KEY (point_vente_id) REFERENCES point_vente (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_723705D1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE utilisateur_role ADD CONSTRAINT FK_9EE8E650FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_role ADD CONSTRAINT FK_9EE8E650D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
;
        
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande_visite DROP FOREIGN KEY FK_F2D8CA54EFA24D68');
        $this->addSql('ALTER TABLE demande_visite DROP FOREIGN KEY FK_F2D8CA543414710B');
        $this->addSql('ALTER TABLE demande_visite DROP FOREIGN KEY FK_F2D8CA5473A201E5');
        $this->addSql('ALTER TABLE demande_visite DROP FOREIGN KEY FK_F2D8CA542FC0CB0F');
        $this->addSql('ALTER TABLE flux_produit DROP FOREIGN KEY FK_85A0F65AF8724BC1');
        $this->addSql('ALTER TABLE flux_produit DROP FOREIGN KEY FK_85A0F65AF347EFB');
        $this->addSql('ALTER TABLE flux_ravitaillement DROP FOREIGN KEY FK_3B34F787FB88E14F');
        $this->addSql('ALTER TABLE flux_ravitaillement DROP FOREIGN KEY FK_3B34F787EFA24D68');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAFB88E14F');
        $this->addSql('ALTER TABLE point_vente DROP FOREIGN KEY FK_2BBFAADF872034B4');
        $this->addSql('ALTER TABLE point_vente DROP FOREIGN KEY FK_2BBFAADFA500A924');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
        $this->addSql('ALTER TABLE `transaction` DROP FOREIGN KEY FK_723705D1EFA24D68');
        $this->addSql('ALTER TABLE `transaction` DROP FOREIGN KEY FK_723705D1FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_role DROP FOREIGN KEY FK_9EE8E650FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_role DROP FOREIGN KEY FK_9EE8E650D60322AC');
        $this->addSql('DROP TABLE categorie_pdv');
        $this->addSql('DROP TABLE categorie_prod');
        $this->addSql('DROP TABLE demande_visite');
        $this->addSql('DROP TABLE flux_produit');
        $this->addSql('DROP TABLE flux_ravitaillement');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE point_vente');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE `transaction`');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE utilisateur_role');
    }
}
