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
use App\Domain\Enum\TypeTransaction;
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
    description: 'Génère des fausses données pour le développement',
    help: 'Crée un ensemble cohérent de fausses données : utilisateurs, PDV, produits, transactions, etc.'
)]
final class SeedFakerDataCommand extends Command
{
    private SymfonyStyle $io;
    private \Faker\Generator $faker;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
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
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            try {
                $connection->executeStatement("TRUNCATE TABLE $table");
            } catch (\Exception) {
                // Table peut ne pas exister
            }
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
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

        // 1 Admin
        $admin = $this->createUser(
            'Système',
            'Admin',
            'admin@mtnpdv.test',
            '+237671234567',
            'password123',
            StatutUtilisateur::ACTIF,
            [$roles['ADMIN']]
        );
        $users['admin'] = $admin;

        // 3 Agents
        $agentNames = [
            ['Dupont', 'Jean'],
            ['Martin', 'Pierre'],
            ['Bernard', 'Paul'],
        ];
        $agentEmails = ['agent1@mtnpdv.test', 'agent2@mtnpdv.test', 'agent3@mtnpdv.test'];
        $agentPhones = ['+237671234568', '+237671234569', '+237671234570'];

        foreach ($agentNames as $index => $names) {
            $agent = $this->createUser(
                $names[0],
                $names[1],
                $agentEmails[$index],
                $agentPhones[$index],
                'password123',
                StatutUtilisateur::ACTIF,
                [$roles['AGENT']]
            );
            $users['agent' . ($index + 1)] = $agent;
        }

        // 2 Gérants
        $gerantNames = [
            ['Diop', 'Amara'],
            ['Sow', 'Ousmane'],
        ];
        $gerantEmails = ['gerant1@mtnpdv.test', 'gerant2@mtnpdv.test'];
        $gerantPhones = ['+237671234571', '+237671234572'];

        foreach ($gerantNames as $index => $names) {
            $gerant = $this->createUser(
                $names[0],
                $names[1],
                $gerantEmails[$index],
                $gerantPhones[$index],
                'password123',
                StatutUtilisateur::ACTIF,
                [$roles['GERANT']]
            );
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
            ['Supérette', 'Petits commerces généraux'],
            ['Boutique', 'Petits magasins spécialisés'],
            ['Kiosque', 'Points de vente très petits'],
            ['Épicerie', 'Produits frais et alimentaires'],
            ['Marché', 'Espaces commerciaux importants'],
        ];

        $result = [];
        foreach ($categories as [$libelle, $description]) {
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
            ['Boissons', 'BOISSON'],
            ['Snacks', 'ALIMENTAIRE'],
            ['Hygiène', 'COSMETIQUE'],
            ['Autres', 'DIVERS'],
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
        $produitData = [
            0 => [ // Boissons
                ['Eau Minérale 1L', 500],
                ['Coca Cola 33cl', 800],
                ['Sprite 33cl', 800],
                ['Jus d\'Orange 1L', 1200],
                ['Bière Kronenbourg 33cl', 1500],
                ['Vin Rouge 75cl', 3500],
                ['Café Nescafé 50g', 2000],
                ['Thé Lipton 25 sachets', 1500],
            ],
            1 => [ // Snacks
                ['Biscuits LU 200g', 1200],
                ['Chips Lay\'s 45g', 500],
                ['Chocolat Kinder', 600],
                ['Bonbons Halls', 300],
                ['Pain de Mie Baguette', 1000],
                ['Cacahuètes Grillées 200g', 1800],
            ],
            2 => [ // Hygiène
                ['Savon Dettol 150g', 600],
                ['Dentifrice Colgate 75ml', 1000],
                ['Shampoing Head & Shoulders 200ml', 1500],
                ['Mouchoirs Kleenex 100', 500],
                ['Papier Hygiénique 4 rouleaux', 1200],
                ['Gel Antibactérien 50ml', 800],
                ['Déodorant Rexona', 1200],
            ],
            3 => [ // Autres
                ['Batteries AA x2', 2000],
                ['Ampoule LED 9W', 1500],
                ['Cahier 100 pages', 500],
                ['Stylo Bic Cristal', 200],
                ['Ruban Adhésif 50m', 800],
            ],
        ];

        $result = [];
        foreach ($produitData as $catIndex => $products) {
            foreach ($products as [$nom, $prixCentimes]) {
                $produit = new Produit($nom, '', Montant::fromCentimes($prixCentimes));
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
        // Coordonnées de base (Douala, Cameroun)
        $baseLat = 3.8667;
        $baseLng = 11.5167;

        $result = [];
        $pdvIndex = 1;
        $gerantIndex = 0;
        $gerants = array_values(array_filter($users, fn($k) => str_starts_with($k, 'gerant'), ARRAY_FILTER_USE_KEY));

        foreach ($categories as $catIndex => $category) {
            for ($i = 0; $i < 3; $i++) {
                // Coordonnées légèrement différentes pour chaque PDV
                $lat = $baseLat + (($i - 1) * 0.005) + ($catIndex * 0.001);
                $lng = $baseLng + (($i - 1) * 0.005) + ($catIndex * 0.001);

                $pdv = new PointVente(
                    "PDV " . $category->getLibelleCatpdv() . " " . ($i + 1),
                    sprintf('PDV-%03d', $pdvIndex),
                    new Coordonnees((string) $lat, (string) $lng),
                    'Douala',
                    Telephone::fromString(sprintf('+237671234%03d', $pdvIndex))
                );

                $pdv->setCategoriePdv($category);
                $pdv->setGerant($gerants[$gerantIndex % count($gerants)]);
                $pdv->setStatutActuel(StatutPointVente::ACTIF);
                $pdv->setAdresse("Centre-Ville, Douala");

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

                // Statut aléatoire
                $statut = random_int(0, 1) === 0 ? 'EN_ATTENTE' : 'LIVRE';
                $flux->changerStatut(\App\Domain\Enum\StatutFlux::from($statut));

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
        $baseLat = 3.8667;
        $baseLng = 11.5167;

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

                // Statut aléatoire
                $statuts = [StatutTransaction::EN_ATTENTE, StatutTransaction::VALIDEE, StatutTransaction::REJETEE];
                $statut = $statuts[random_int(0, 2)];

                if ($statut === StatutTransaction::VALIDEE) {
                    $transaction->valider();
                } elseif ($statut === StatutTransaction::REJETEE) {
                    $transaction->rejeter();
                }

                $this->entityManager->persist($transaction);
            }
        }

        $this->entityManager->flush();
    }

    private function generateNotifications(array $users): void
    {
        $notificationMessages = [
            TypeNotification::VISITE_VALIDEE => ['titre' => 'Visite validée', 'message' => 'Votre visite a été validée par un administrateur.'],
            TypeNotification::VISITE_REJETEE => ['titre' => 'Visite rejetée', 'message' => 'Votre visite a été rejetée. Veuillez contacter un administrateur.'],
            TypeNotification::FLUX_LIVRE => ['titre' => 'Flux de ravitaillement livré', 'message' => 'Votre flux de ravitaillement a été traité.'],
            TypeNotification::ALERTE_GERANT => ['titre' => 'Alerte', 'message' => 'Une action est requise sur votre kiosque.'],
        ];

        foreach ($users as $user) {
            $nbNotifications = random_int(2, 5);

            for ($n = 0; $n < $nbNotifications; $n++) {
                $notifType = $this->faker->randomElement(array_keys($notificationMessages));
                $data = $notificationMessages[$notifType];

                $notification = new Notification(
                    $user,
                    $notifType,
                    $data['titre'],
                    $data['message'],
                    '/dashboard'
                );

                $this->entityManager->persist($notification);
            }
        }

        $this->entityManager->flush();
    }
}
