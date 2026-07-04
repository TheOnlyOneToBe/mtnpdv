<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Domain\Entity\CategoriePdv;
use App\Domain\Entity\CategorieProd;
use App\Domain\Entity\Notification;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Produit;
use App\Domain\Entity\Role;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutPointVente;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\StatutUtilisateur;
use App\Domain\Enum\TypeNotification;
use App\Domain\Enum\TypeProblemeSupervision;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Telephone;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Commande pour générer des fausses données de développement.
 *
 * Usage:
 *   php bin/console app:seed:faker [--reset]
 *
 * Options:
 *   --reset : Supprimer les anciennes données avant la génération
 */
#[AsCommand(
    name: 'app:seed:faker',
    description: 'Génère des fausses données pour le développement'
)]
final class SeedFakerDataCommand extends Command
{
    private SymfonyStyle $io;
    private \Faker\Generator $faker;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
    ) {
        parent::__construct();
        $this->faker = Factory::create('fr_FR');
    }

    protected function configure(): void
    {
        $this
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Supprimer les données existantes avant la génération')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $this->io->title('🌱 Génération des Fausses Données');

            if ($input->getOption('reset')) {
                $this->io->section('Suppression des anciennes données');
                $this->deleteExistingData();
            }

            $this->io->section('Création des entités');

            $roles = $this->getOrCreateRoles();
            $this->io->writeln('✅ Rôles vérifiés/créés');

            $users = $this->generateUsers($roles);
            $this->io->writeln('✅ Utilisateurs générés');

            $categoriesPdv = $this->generateCategoriePdv();
            $this->io->writeln('✅ Catégories PDV générées');

            $categoriesProd = $this->generateCategorieProd();
            $this->io->writeln('✅ Catégories Produits générées');

            $produits = $this->generateProduits($categoriesProd);
            $this->io->writeln('✅ Produits générés');

            $pdvs = $this->generatePointsVente($categoriesPdv, $users);
            $this->io->writeln('✅ Points de vente générés');

            $this->generateFluxRavitaillement($pdvs, $users, $produits);
            $this->io->writeln('✅ Flux de ravitaillement générés');

            $this->generateTransactions($pdvs, $users);
            $this->io->writeln('✅ Transactions générées');

            $this->generateNotifications($users);
            $this->io->writeln('✅ Notifications générées');

            $this->io->success('🎉 Génération des fausses données terminée avec succès !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->io->error('Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function deleteExistingData(): void
    {
        $tables = [
            'notification',
            'flux_produit',
            'flux_ravitaillement',
            '`transaction`',
            'utilisateur_role',
            'utilisateur',
            'point_vente',
            'categorie_pdv',
            'produit',
            'categorie_prod',
        ];

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform;

        // Disable foreign key checks
        if ($isSqlite) {
            $connection->executeStatement('PRAGMA foreign_keys = OFF');
        } else {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach ($tables as $table) {
            try {
                if ($isSqlite) {
                    $connection->executeStatement("DELETE FROM $table");
                } else {
                    $connection->executeStatement("TRUNCATE TABLE $table");
                }
            } catch (\Exception) {
                // Table peut ne pas exister
            }
        }

        // Re-enable foreign key checks
        if ($isSqlite) {
            $connection->executeStatement('PRAGMA foreign_keys = ON');
        } else {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }
        $this->io->writeln('Données existantes supprimées');
    }

    private function getOrCreateRoles(): array
    {
        $roleRepository = $this->entityManager->getRepository(Role::class);

        $roles = [];
        $roleData = [
            'ADMIN' => 'Administrateur',
            'AGENT' => 'Agent Terrain',
            'GERANT' => 'Gérant de PDV',
        ];

        foreach ($roleData as $code => $label) {
            $role = $roleRepository->findOneBy(['codeRole' => $code]);
            if (!$role) {
                $role = new Role($code, $label);
                $this->entityManager->persist($role);
            }
            $roles[$code] = $role;
        }

        $this->entityManager->flush();
        return $roles;
    }

    private function generateUsers(array $roles): array
    {
        $users = [];

        // 1 Admin - Cameroonian name with corporate email
        $adminEmail = Email::fromString('admin@tinglobal.cm');
        $admin = $this->utilisateurRepository->findOneByEmail($adminEmail);
        if (!$admin) {
            $admin = $this->createUser(
                'Ndzi',
                'Jean',
                'admin@tinglobal.cm',
                '+237670123456',
                'password123',
                StatutUtilisateur::ACTIF,
                [$roles['ADMIN']]
            );
        }
        $users['admin'] = $admin;

        // 3 Agents - Cameroonian names with mixed email domains
        $agentData = [
            ['Mbah', 'Paul', 'paul.mbah@gmail.com', '+237691234567'],
            ['Tchoua', 'Mireille', 'mireille.tchoua@gmail.com', '+237692345678'],
            ['Dibango', 'Sophie', 'sophie.dibango@gmail.com', '+237693456789'],
        ];

        foreach ($agentData as $index => $data) {
            $agentEmail = Email::fromString($data[2]);
            $agent = $this->utilisateurRepository->findOneByEmail($agentEmail);
            if (!$agent) {
                $agent = $this->createUser(
                    $data[0],
                    $data[1],
                    $data[2],
                    $data[3],
                    'password123',
                    StatutUtilisateur::ACTIF,
                    [$roles['AGENT']]
                );
            }
            $users['agent' . ($index + 1)] = $agent;
        }

        // 2 Gérants - Cameroonian names with mixed email domains
        $gerantData = [
            ['Tamban', 'Hervé', 'herve.tamban@gmail.com', '+237694567890'],
            ['Tokoto', 'Grace', 'grace.tokoto@gmail.com', '+237695678901'],
        ];

        foreach ($gerantData as $index => $data) {
            $gerantEmail = Email::fromString($data[2]);
            $gerant = $this->utilisateurRepository->findOneByEmail($gerantEmail);
            if (!$gerant) {
                $gerant = $this->createUser(
                    $data[0],
                    $data[1],
                    $data[2],
                    $data[3],
                    'password123',
                    StatutUtilisateur::ACTIF,
                    [$roles['GERANT']]
                );
            }
            $users['gerant' . ($index + 1)] = $gerant;
        }

        $this->entityManager->flush();
        return $users;
    }

    private function createUser(
        string $nom,
        string $prenom,
        string $email,
        string $telephone,
        string $plainPassword,
        StatutUtilisateur $statut,
        array $roles
    ): Utilisateur {
        // Create user first with placeholder password
        $tempPhone = Telephone::fromString('+237600000000');
        $tempEmail = Email::fromString('temp@temp.test');

        $user = new Utilisateur($nom, $prenom, $tempEmail, 'temp', $tempPhone);
        $user->setStatut($statut);

        // Now set the actual email, phone, and hashed password
        $user->setEmail(Email::fromString($email));
        $user->setTelephone(Telephone::fromString($telephone));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        foreach ($roles as $role) {
            $user->addRole($role);
        }

        $this->entityManager->persist($user);
        return $user;
    }

    private function generateCategoriePdv(): array
    {
        $categories = [
            'Kiosque MTN',
            'Agence MTN',
            'Boutique Partenaire',
            'Revendeur Agréé',
            'Supermarché Partenaire',
        ];

        $result = [];
        foreach ($categories as $libelle) {
            $cat = new CategoriePdv($libelle);
            $this->entityManager->persist($cat);
            $result[] = $cat;
        }

        $this->entityManager->flush();
        return $result;
    }

    private function generateCategorieProd(): array
    {
        $categories = [
            ['Flotte', 'SERVICE'],
            ['Espèce', 'SERVICE'],
        ];

        $result = [];
        foreach ($categories as [$libelle, $type]) {
            $cat = new CategorieProd($libelle, $type);
            $this->entityManager->persist($cat);
            $result[] = $cat;
        }

        $this->entityManager->flush();
        return $result;
    }

    private function generateProduits(array $categories): array
    {
        // MTN Cameroon - Flotte (argent digital) et Espèce (cash)
        $produitData = [
            0 => [ // Flotte (Argent dans le téléphone)
                ['Flotte 1000 FCFA', 100000],
                ['Flotte 2500 FCFA', 250000],
                ['Flotte 5000 FCFA', 500000],
                ['Flotte 10000 FCFA', 1000000],
                ['Flotte 25000 FCFA', 2500000],
                ['Flotte 50000 FCFA', 5000000],
            ],
            1 => [ // Espèce (Argent en cash/versement)
                ['Espèce 1000 FCFA', 100000],
                ['Espèce 2500 FCFA', 250000],
                ['Espèce 5000 FCFA', 500000],
                ['Espèce 10000 FCFA', 1000000],
                ['Espèce 25000 FCFA', 2500000],
                ['Espèce 50000 FCFA', 5000000],
            ],
        ];

        $result = [];
        foreach ($produitData as $catIndex => $products) {
            foreach ($products as [$nom, $prixCentimes]) {
                $produit = new Produit($nom, 'Produit MTN Cameroon', Montant::fromCentimes($prixCentimes));
                $produit->setCategorie($categories[$catIndex]);
                $this->entityManager->persist($produit);
                $result[] = $produit;
            }
        }

        $this->entityManager->flush();
        return $result;
    }

    private function generatePointsVente(array $categories, array $users): array
    {
        // Coordonnées par ville et quartier (Cameroun)
        $locations = [
            // Douala
            ['Douala', 'Akwa', 4.0511, 9.7679],
            ['Douala', 'Bonanjo', 4.0548, 9.7385],
            ['Douala', 'Bonabéri', 3.9894, 9.6908],
            ['Douala', 'Deido', 4.0189, 9.7428],
            ['Douala', 'Makepe', 4.0850, 9.7206],
            // Yaoundé
            ['Yaoundé', 'Bastos', 3.8667, 11.5167],
            ['Yaoundé', 'Biyem-Assi', 3.8420, 11.5420],
            ['Yaoundé', 'Mvog-Ada', 3.8380, 11.4920],
            ['Yaoundé', 'Nkolbisson', 3.8550, 11.4750],
            ['Yaoundé', 'Essos', 3.8920, 11.5520],
        ];

        $statuts = [
            StatutPointVente::ACTIF,
            StatutPointVente::ACTIF,
            StatutPointVente::ACTIF,
            StatutPointVente::INACTIF,
            StatutPointVente::SUSPENDU, // Temporairement fermé / en maintenance
        ];

        $result = [];
        $pdvIndex = 1;
        $gerantIndex = 0;
        $gerants = array_values(array_filter($users, fn($k) => str_starts_with($k, 'gerant'), ARRAY_FILTER_USE_KEY));

        foreach ($locations as $locationIndex => $location) {
            [$ville, $quartier, $baseLat, $baseLng] = $location;

            // Créer 1-2 PDV par location
            $pdvPerLocation = ($locationIndex < 2) ? 2 : 1; // Plus de PDV à Akwa et Bastos

            for ($i = 0; $i < $pdvPerLocation; $i++) {
                // Coordonnées légèrement différentes pour chaque PDV dans le quartier
                $latOffset = (random_int(-50, 50)) / 111000; // ±50m
                $lngOffset = (random_int(-50, 50)) / (111000 * cos(deg2rad($baseLat))); // ±50m

                $lat = $baseLat + $latOffset;
                $lng = $baseLng + $lngOffset;

                // Sélectionner une catégorie basée sur l'index
                $category = $categories[$pdvIndex % count($categories)];

                // Générer un nom réaliste pour le PDV
                $pdvNames = [
                    "Kiosque {$quartier}",
                    "{$quartier} Express",
                    "MTN {$quartier}",
                    "Point Vente {$quartier}",
                    "Boutique {$quartier} MTN",
                ];
                $pdvName = $pdvNames[$pdvIndex % count($pdvNames)];

                $pdv = new PointVente(
                    $pdvName,
                    sprintf('PDV-%03d', $pdvIndex),
                    new Coordonnees((string) $lat, (string) $lng),
                    $ville,
                    Telephone::fromString(sprintf('+237%d%06d', random_int(6, 7), random_int(0, 999999)))
                );

                $pdv->setCategoriePdv($category);
                $pdv->setGerant($gerants[$gerantIndex % count($gerants)]);
                $pdv->setStatutActuel($statuts[$pdvIndex % count($statuts)]);
                $pdv->setAdresse("{$quartier}, {$ville}");

                $this->entityManager->persist($pdv);
                $result[] = $pdv;
                $pdvIndex++;
                $gerantIndex++;
            }
        }

        $this->entityManager->flush();
        return $result;
    }

    private function generateFluxRavitaillement(array $pdvs, array $users, array $produits): void
    {
        $agents = array_values(array_filter($users, fn($k) => str_starts_with($k, 'agent'), ARRAY_FILTER_USE_KEY));

        foreach ($pdvs as $pdvIndex => $pdv) {
            $nbFlux = random_int(1, 2);

            for ($f = 0; $f < $nbFlux; $f++) {
                $factureId = sprintf('FLUX-PDV%03d-%s-%d', $pdv->getId(), date('Ymd'), $f);
                $flux = new \App\Domain\Entity\FluxRavitaillement($factureId);

                $flux->setPointVente($pdv);
                $flux->setUtilisateur($agents[random_int(0, count($agents) - 1)]);

                // Statut aléatoire (respecter les transitions)
                $rd = random_int(0, 100);
                if ($rd < 30) {
                    // EN_ATTENTE (défaut)
                } elseif ($rd < 60) {
                    // EN_ATTENTE → VALIDE
                    $flux->changerStatut(\App\Domain\Enum\StatutFlux::VALIDE);
                    if (random_int(0, 1) === 0) {
                        // VALIDE → EXPEDIE
                        $flux->changerStatut(\App\Domain\Enum\StatutFlux::EXPEDIE);
                        if (random_int(0, 1) === 0) {
                            // EXPEDIE → LIVRE
                            $flux->changerStatut(\App\Domain\Enum\StatutFlux::LIVRE);
                        }
                    }
                } else {
                    // EN_ATTENTE → ANNULE
                    $flux->changerStatut(\App\Domain\Enum\StatutFlux::ANNULE);
                }

                // Ajouter des lignes de produits
                $nbLignes = random_int(3, 8);
                $selectedProduits = array_slice($produits, 0, $nbLignes);

                foreach ($selectedProduits as $produit) {
                    $quantite = random_int(5, 50);
                    $flux->ajouterLigne($produit, $quantite);
                }

                $this->entityManager->persist($flux);
            }
        }

        $this->entityManager->flush();
    }

    private function generateTransactions(array $pdvs, array $users): void
    {
        $agents = array_values(array_filter($users, fn($k) => str_starts_with($k, 'agent'), ARRAY_FILTER_USE_KEY));

        // Types de problèmes possibles
        $problemes = TypeProblemeSupervision::cases();

        foreach ($pdvs as $pdv) {
            $nbTransactions = random_int(3, 5);

            for ($t = 0; $t < $nbTransactions; $t++) {
                $montantCentimes = random_int(1000, 10000000); // 10 FCFA à 100 000 FCFA
                $montant = Montant::fromCentimes($montantCentimes);

                // Position GPS proche du PDV (±50-150m)
                $pdvCoords = $pdv->getCoordonnees();
                $latOffset = (random_int(-150, 150)) / 111000; // 1° ≈ 111km
                $lngOffset = (random_int(-150, 150)) / (111000 * cos(deg2rad($pdvCoords->latitude())));

                $coordsCapture = new Coordonnees(
                    (string) ($pdvCoords->latitude() + $latOffset),
                    (string) ($pdvCoords->longitude() + $lngOffset)
                );

                $transaction = new Transaction(
                    TypeTransaction::VISITE,
                    $montant,
                    $coordsCapture
                );

                $transaction->setPointVente($pdv);
                $transaction->setUtilisateur($agents[random_int(0, count($agents) - 1)]);

                // 70% de chance: aucun problème, 30% chance: un problème
                if (random_int(1, 100) <= 30) {
                    $problemIndex = random_int(0, count($problemes) - 1);
                    $transaction->setTypeProbleme($problemes[$problemIndex]);
                } else {
                    $transaction->setTypeProbleme(TypeProblemeSupervision::AUCUN_PROBLEME);
                }

                // Statut aléatoire
                $statuts = [StatutTransaction::EN_ATTENTE, StatutTransaction::VALIDEE, StatutTransaction::REJETEE];
                $statut = $statuts[random_int(0, 2)];

                if ($statut === StatutTransaction::VALIDEE) {
                    $transaction->valider();
                } elseif ($statut === StatutTransaction::REJETEE) {
                    $transaction->rejeter();
                }

                // Ajouter un commentaire basé sur le type de problème
                if ($transaction->getTypeProbleme() !== null) {
                    $comment = match ($transaction->getTypeProbleme()) {
                        TypeProblemeSupervision::RUPTURE_STOCK => 'Certains produits MTN sont en rupture de stock',
                        TypeProblemeSupervision::ABSENCE_GERANT => 'Le gérant était absent lors de la visite',
                        TypeProblemeSupervision::CONNEXION_INTERNET_INDISPONIBLE => 'Problème de connectivité Internet détecté',
                        TypeProblemeSupervision::PROBLEME_TERMINAL_MOMO => 'Le terminal MoMo ne fonctionne pas correctement',
                        TypeProblemeSupervision::PROBLEME_ALIMENTATION_ELECTRIQUE => 'Problème d\'alimentation électrique',
                        TypeProblemeSupervision::FERMETURE_EXCEPTIONNELLE => 'Le point de vente était fermé',
                        TypeProblemeSupervision::CLIENT_INSATISFAIT => 'Un client a exprimé son insatisfaction',
                        TypeProblemeSupervision::BESOIN_FONDS_ROULEMENT => 'Besoin de renforcer les fonds de roulement',
                        TypeProblemeSupervision::POINT_VENTE_INACCESSIBLE => 'Difficulté d\'accès au point de vente',
                        TypeProblemeSupervision::AUCUN_PROBLEME => 'Visite effectuée sans problème',
                    };
                    $transaction->setCommentaireRapport($comment);
                }

                $this->entityManager->persist($transaction);
            }
        }

        $this->entityManager->flush();
    }

    private function generateNotifications(array $users): void
    {
        $notificationTypes = [
            [TypeNotification::VISITE_VALIDEE, 'Visite validée', 'Votre visite a été validée par un administrateur.'],
            [TypeNotification::VISITE_REJETEE, 'Visite rejetée', 'Votre visite a été rejetée. Veuillez contacter un administrateur.'],
            [TypeNotification::VISITE_CREEE, 'Nouvelle visite créée', 'Une nouvelle visite vous a été assignée.'],
            [TypeNotification::PRODUIT_LIVRE, 'Produit livré', 'Un produit a été livré à votre point de vente.'],
            [TypeNotification::MESSAGE_ADMIN, 'Message administrateur', 'Vous avez reçu un message de l\'administrateur.'],
            [TypeNotification::ALERTE_SYSTEME, 'Alerte système', 'Une alerte système a été générée.'],
        ];

        foreach ($users as $user) {
            $nbNotifications = random_int(2, 5);

            for ($n = 0; $n < $nbNotifications; $n++) {
                $data = $this->faker->randomElement($notificationTypes);

                $notification = new Notification(
                    $user,
                    $data[0],
                    $data[1],
                    $data[2],
                    '/dashboard'
                );

                $this->entityManager->persist($notification);
            }
        }

        $this->entityManager->flush();
    }
}
