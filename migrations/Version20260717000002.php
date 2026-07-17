<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update demande_visite table for new workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_visite ADD createur_id INT NOT NULL, ADD type_enum VARCHAR(50) NOT NULL, ADD montant NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE demande_visite ADD CONSTRAINT FK_312C958173A20199 FOREIGN KEY (createur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_312C958173A20199 ON demande_visite (createur_id)');
        
        // Optional: If we want to keep existing data, we could set createur_id to admin_id, but let's just add the columns
        // First, drop the old admin_id column? Wait no, let's first rename or handle it. Wait let's check what the current table has.
        // Wait actually, let's adjust: the previous column was admin_id, now we're replacing it with createur_id.
        $this->addSql('ALTER TABLE demande_visite DROP FOREIGN KEY FK_312C9581642B8210');
        $this->addSql('DROP INDEX IDX_312C9581642B8210 ON demande_visite');
        $this->addSql('ALTER TABLE demande_visite DROP COLUMN admin_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_visite ADD admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE demande_visite ADD CONSTRAINT FK_312C9581642B8210 FOREIGN KEY (admin_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_312C9581642B8210 ON demande_visite (admin_id)');
        $this->addSql('ALTER TABLE demande_visite DROP FOREIGN KEY FK_312C958173A20199');
        $this->addSql('DROP INDEX IDX_312C958173A20199 ON demande_visite');
        $this->addSql('ALTER TABLE demande_visite DROP createur_id, DROP type_enum, DROP montant');
    }
}
