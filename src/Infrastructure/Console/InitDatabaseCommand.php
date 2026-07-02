<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Domain\Entity\CategoriePdv;
use App\Domain\Entity\CategorieProd;
use App\Domain\Entity\FluxProduit;
use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Entity\Notification;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Produit;
use App\Domain\Entity\Role;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutFlux;
use App\Domain\Enum\StatutTransaction;
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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

#[AsCommand(
    name: 'app:init:database',
    description: 'Initialize database with migrations and fake data for 3 months of usage',
)]
class InitDatabaseCommand extends Command
{
    // MTN Cameroon real locations with GPS coordinates
    private const MTN_AGENCIES = [
        ['nom' => 'MTN Douala - Boulevard de la Liberté', 'ville' => 'Douala', 'lat' => 4.0511, 'lng' => 9.7679, 'adresse' => 'Boulevard de la Liberté, Douala'],
        ['nom' => 'MTN Douala - New Bell', 'ville' => 'Douala', 'lat' => 4.0453, 'lng' => 9.7590, 'adresse' => 'New Bell, Douala'],
        ['nom' => 'MTN Douala - Akwa', 'ville' => 'Douala', 'lat' => 4.0389, 'lng' => 9.7403, 'adresse' => 'Akwa, Douala'],
        ['nom' => 'MTN Douala - Bonamoussadi', 'ville' => 'Douala', 'lat' => 4.0202, 'lng' => 9.6920, 'adresse' => 'Bonamoussadi, Douala'],
        ['nom' => 'MTN Douala - Deido', 'ville' => 'Douala', 'lat' => 4.0060, 'lng' => 9.7110, 'adresse' => 'Deido, Douala'],

        ['nom' => 'MTN Yaoundé - Centre Ville', 'ville' => 'Yaoundé', 'lat' => 3.8667, 'lng' => 11.5167, 'adresse' => 'Centre-ville, Yaoundé'],
        ['nom' => 'MTN Yaoundé - Plateau Atemengué', 'ville' => 'Yaoundé', 'lat' => 3.8526, 'lng' => 11.5020, 'adresse' => 'Plateau Atemengué, Yaoundé'],
        ['nom' => 'MTN Yaoundé - Omnisports', 'ville' => 'Yaoundé', 'lat' => 3.8774, 'lng' => 11.4924, 'adresse' => 'Omnisports, Yaoundé'],
        ['nom' => 'MTN Yaoundé - Bastos', 'ville' => 'Yaoundé', 'lat' => 3.8451, 'lng' => 11.4923, 'adresse' => 'Bastos, Yaoundé'],
        ['nom' => 'MTN Yaoundé - Carrefour', 'ville' => 'Yaoundé', 'lat' => 3.8799, 'lng' => 11.5025, 'adresse' => 'Carrefour, Yaoundé'],

        ['nom' => 'MTN Kumba', 'ville' => 'Kumba', 'lat' => 4.6448, 'lng' => 9.4417, 'adresse' => 'Centre-ville, Kumba'],
        ['nom' => 'MTN Limbe', 'ville' => 'Limbe', 'lat' => 4.0247, 'lng' => 9.2097, 'adresse' => 'Centre-ville, Limbe'],
        ['nom' => 'MTN Buea', 'ville' => 'Buea', 'lat' => 4.1520, 'lng' => 9.2412, 'adresse' => 'Centre-ville, Buea'],

        ['nom' => 'MTN Bamenda', 'ville' => 'Bamenda', 'lat' => 5.9631, 'lng' => 10.1591, 'adresse' => 'Centre-ville, Bamenda'],
        ['nom' => 'MTN Bafoussam', 'ville' => 'Bafoussam', 'lat' => 5.7679, 'lng' => 10.4167, 'adresse' => 'Centre-ville, Bafoussam'],
        ['nom' => 'MTN Mbouda', 'ville' => 'Mbouda', 'lat' => 5.6318, 'lng' => 10.2400, 'adresse' => 'Centre-ville, Mbouda'],

        ['nom' => 'MTN Garoua', 'ville' => 'Garoua', 'lat' => 9.3022, 'lng' => 13.3972, 'adresse' => 'Centre-ville, Garoua'],
        ['nom' => 'MTN Ngaoundéré', 'ville' => 'Ngaoundéré', 'lat' => 7.3242, 'lng' => 13.5833, 'adresse' => 'Centre-ville, Ngaoundéré'],

        ['nom' => 'MTN Bertoua', 'ville' => 'Bertoua', 'lat' => 4.5833, 'lng' => 13.6833, 'adresse' => 'Centre-ville, Bertoua'],
        ['nom' => 'MTN Batouri', 'ville' => 'Batouri', 'lat' => 4.4500, 'lng' => 14.3667, 'adresse' => 'Centre-ville, Batouri'],

        ['nom' => 'MTN Yaoundé - Mvog-Mbi', 'ville' => 'Yaoundé', 'lat' => 3.8897, 'lng' => 11.5036, 'adresse' => 'Mvog-Mbi, Yaoundé'],
        ['nom' => 'MTN Douala - Makepe', 'ville' => 'Douala', 'lat' => 4.0714, 'lng' => 9.7606, 'adresse' => 'Makepe, Douala'],
        ['nom' => 'MTN Douala - Bonanjo', 'ville' => 'Douala', 'lat' => 4.0256, 'lng' => 9.7090, 'adresse' => 'Bonanjo, Douala'],

        ['nom' => 'MTN Kribi', 'ville' => 'Kribi', 'lat' => 2.9375, 'lng' => 10.1689, 'adresse' => 'Centre-ville, Kribi'],
        ['nom' => 'MTN Edéa', 'ville' => 'Edéa', 'lat' => 3.7833, 'lng' => 10.1333, 'adresse' => 'Centre-ville, Edéa'],

        ['nom' => 'MTN Ebolowa', 'ville' => 'Ebolowa', 'lat' => 2.9167, 'lng' => 11.1500, 'adresse' => 'Centre-ville, Ebolowa'],
    ];

    // Real MTN Cameroon products/services
    private const MTN_PRODUCTS = [
        ['nom' => 'Recharge 500 CFA', 'categorie' => 'Recharges', 'prix' => 50000],
        ['nom' => 'Recharge 1000 CFA', 'categorie' => 'Recharges', 'prix' => 100000],
        ['nom' => 'Recharge 2000 CFA', 'categorie' => 'Recharges', 'prix' => 200000],
        ['nom' => 'Recharge 5000 CFA', 'categorie' => 'Recharges', 'prix' => 500000],
        ['nom' => 'Recharge 10000 CFA', 'categorie' => 'Recharges', 'prix' => 1000000],

        ['nom' => 'Data 200MB/7j', 'categorie' => 'Forfaits Data', 'prix' => 150000],
        ['nom' => 'Data 500MB/30j', 'categorie' => 'Forfaits Data', 'prix' => 500000],
        ['nom' => 'Data 1GB/30j', 'categorie' => 'Forfaits Data', 'prix' => 900000],
        ['nom' => 'Data 5GB/30j', 'categorie' => 'Forfaits Data', 'prix' => 4000000],

        ['nom' => 'Forfait ZONE 1', 'categorie' => 'Forfaits Appels', 'prix' => 600000],
        ['nom' => 'Forfait ZONE 2', 'categorie' => 'Forfaits Appels', 'prix' => 1200000],
        ['nom' => 'Forfait ZONE 3', 'categorie' => 'Forfaits Appels', 'prix' => 2500000],

        ['nom' => 'SMS 100', 'categorie' => 'Forfaits SMS', 'prix' => 300000],
        ['nom' => 'SMS 200', 'categorie' => 'Forfaits SMS', 'prix' => 500000],

        ['nom' => 'Mobile Money MTN', 'categorie' => 'Services Financiers', 'prix' => 0],
        ['nom' => 'Insurance MTN', 'categorie' => 'Services Financiers', 'prix' => 100000],

        ['nom' => 'Modem WiFi', 'categorie' => 'Équipements', 'prix' => 15000000],
        ['nom' => 'Téléphone Classique', 'categorie' => 'Équipements', 'prix' => 25000000],
        ['nom' => 'Téléphone Smartphone', 'categorie' => 'Équipements', 'prix' => 150000000],

        ['nom' => 'Carte Prépayée 1 mois', 'categorie' => 'Cartes', 'prix' => 2000000],
        ['nom' => 'Carte Prépayée 3 mois', 'categorie' => 'Cartes', 'prix' => 5500000],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'with-data',
                null,
                InputOption::VALUE_NONE,
                'Generate fake data for 3 months (will ask for confirmation if not provided)',
            )
            ->addOption(
                'no-data',
                null,
                InputOption::VALUE_NONE,
                'Skip fake data generation',
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Skip confirmation prompts',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create('fr_FR');

        $io->title('🚀 MTNPDV - Database Initialization');
        $io->writeln('📍 MTN Cameroon - Points of Sale Management System');

        try {
            // Step 1: Clear database
            $io->section('Step 1: Clearing database');
            if (!$input->getOption('force')) {
                $question = new ConfirmationQuestion('Are you sure you want to delete ALL data? (yes/no): ', false);
                if (!$this->getHelper('question')->ask($input, $output, $question)) {
                    $io->warning('Operation cancelled');
                    return Command::FAILURE;
                }
            }
            $this->clearDatabase();
            $io->success('Database cleared');

            // Step 2: Create base roles
            $io->section('Step 2: Creating base roles');
            $roles = $this->createRoles();
            $io->success(sprintf('Created %d roles', count($roles)));

            // Step 3: Create categories
            $io->section('Step 3: Creating categories');
            $categoriesPdv = $this->createCategoriePdv();
            $categoriesProd = $this->createCategorieProd();
            $io->success(sprintf('Created %d PDV categories and %d product categories', count($categoriesPdv), count($categoriesProd)));

            // Step 4: Ask about fake data
            $withData = $input->getOption('with-data');
            if (!$withData && !$input->getOption('no-data') && !$input->getOption('force')) {
                $question = new ConfirmationQuestion('Generate fake data for 3 months? (yes/no): ', false);
                $withData = $this->getHelper('question')->ask($input, $output, $question);
            }

            if ($withData && !$input->getOption('no-data')) {
                $io->section('Step 5: Generating fake data for 3 months');

                // Create users
                $io->writeln('Creating users...');
                $adminUser = $this->createAdmin($roles['ADMIN'], $faker);
                $agents = $this->createAgents(15, $roles['AGENT'], $faker);
                $gerants = $this->createGerants(8, $roles['GERANT'], $faker);
                $io->success(sprintf('Created 1 admin, %d agents, %d gérants', count($agents), count($gerants)));

                // Create products
                $io->writeln('Creating MTN products...');
                $products = $this->createMtnProducts($categoriesProd);
                $io->success(sprintf('Created %d MTN products', count($products)));

                // Create points of sale (MTN agencies)
                $io->writeln('Creating MTN agencies...');
                $pdvs = $this->createMtnAgencies($gerants, $categoriesPdv);
                $io->success(sprintf('Created %d MTN agencies across Cameroon', count($pdvs)));

                // Create supply flows for 3 months
                $io->writeln('Creating supply flows for 3 months...');
                $suppliedPdvs = $this->createSupplyFlows(180, $pdvs, $products, $faker);
                $io->success(sprintf('Created supply flows for %d agencies', count($suppliedPdvs)));

                // Create visits/transactions for 3 months
                $io->writeln('Creating agent visits for 3 months...');
                $transactions = $this->createTransactions(400, $agents, $pdvs, $faker);
                $io->success(sprintf('Created %d visits/transactions', count($transactions)));

                // Create notifications
                $io->writeln('Creating notifications...');
                $notifications = $this->createNotifications($agents, $transactions, $faker);
                $io->success(sprintf('Created %d notifications', count($notifications)));

                $io->newLine();
                $io->info('✅ Fake data generation completed!');
                $io->writeln([
                    '',
                    '📊 Summary:',
                    '  Users: 1 Admin, ' . count($agents) . ' Agents, ' . count($gerants) . ' Gérants (MTN staff)',
                    '  MTN Agencies: ' . count($pdvs) . ' across Cameroon',
                    '  Products: ' . count($products) . ' (MTN services)',
                    '  Transactions: ' . count($transactions) . ' visits/sales over 3 months',
                    '  Notifications: ' . count($notifications),
                    '',
                    '🎯 Test credentials:',
                    '  Email: admin@mtnpdv.local',
                    '  Password: password123',
                ]);
            } else {
                $io->info('Skipping fake data generation');
            }

            $io->success('✅ Database initialization completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Error during initialization: ' . $e->getMessage());
            $io->error('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function clearDatabase(): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();
        $isSQLite = $platform instanceof SQLitePlatform;

        // Disable foreign key checks based on database type
        if ($platform instanceof MySQLPlatform) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($isSQLite) {
            $connection->executeStatement('PRAGMA foreign_keys=OFF');
        }

        $tables = [
            'notification',
            'flux_produit',
            'flux_ravitaillement',
            'transaction',
            'point_vente',
            'produit',
            'utilisateur',
            'categorie_prod',
            'categorie_pdv',
            'role',
        ];

        foreach ($tables as $table) {
            try {
                $connection->executeStatement($platform->getTruncateTableSQL($table, true));
            } catch (\Exception $e) {
                // Table might not exist yet, skip
            }
        }

        // Re-enable foreign key checks
        if ($platform instanceof MySQLPlatform) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($isSQLite) {
            $connection->executeStatement('PRAGMA foreign_keys=ON');
        }
    }

    private function createRoles(): array
    {
        $roles = [];
        $roleDefinitions = [
            'ADMIN' => 'Administrateur système',
            'AGENT' => 'Agent terrain',
            'GERANT' => 'Gérant de point de vente MTN',
        ];

        foreach ($roleDefinitions as $code => $libelle) {
            $role = new Role($code, $libelle);
            $this->em->persist($role);
            $roles[$code] = $role;
        }

        $this->em->flush();
        return $roles;
    }

    private function createCategoriePdv(): array
    {
        $categories = [
            'Boutique MTN',
            'Agence Principale',
            'Mini Boutique',
            'Partenaire Agréé',
            'Représentation Régionale',
        ];

        $result = [];
        foreach ($categories as $name) {
            $cat = new CategoriePdv(libelleCatpdv: $name);
            $this->em->persist($cat);
            $result[] = $cat;
        }

        $this->em->flush();
        return $result;
    }

    private function createCategorieProd(): array
    {
        $categories = [
            ['libelle' => 'Recharges', 'type' => 'Service'],
            ['libelle' => 'Forfaits Data', 'type' => 'Service'],
            ['libelle' => 'Forfaits Appels', 'type' => 'Service'],
            ['libelle' => 'Forfaits SMS', 'type' => 'Service'],
            ['libelle' => 'Services Financiers', 'type' => 'Service'],
            ['libelle' => 'Équipements', 'type' => 'Produit'],
            ['libelle' => 'Cartes', 'type' => 'Produit'],
        ];

        $result = [];
        foreach ($categories as $catData) {
            $cat = new CategorieProd($catData['libelle'], $catData['type']);
            $this->em->persist($cat);
            $result[] = $cat;
        }

        $this->em->flush();
        return $result;
    }

    private function createAdmin(Role $adminRole, $faker): Utilisateur
    {
        $admin = new Utilisateur(
            email: new Email('admin@mtnpdv.local'),
            nomUt: 'Admin',
            prenomUt: 'MTN-POS',
            telephone: new Telephone(faker: $faker),
        );

        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password123'));
        $admin->addRole($adminRole);
        $admin->setStatut('ACTIF');
        $admin->setCoordonnees(new Coordonnees(3.8667, 11.5167)); // Yaoundé

        $this->em->persist($admin);
        $this->em->flush();

        return $admin;
    }

    private function createAgents(int $count, Role $agentRole, $faker): array
    {
        $agents = [];

        for ($i = 0; $i < $count; $i++) {
            $agent = new Utilisateur(
                email: new Email('agent' . ($i + 1) . '@mtnpdv.local'),
                nomUt: $faker->lastName(),
                prenomUt: $faker->firstName(),
                telephone: new Telephone(faker: $faker),
            );

            $agent->setPassword($this->passwordHasher->hashPassword($agent, 'password123'));
            $agent->addRole($agentRole);
            $agent->setStatut('ACTIF');

            // Random agency from MTN agencies
            $randomAgency = $faker->randomElement(self::MTN_AGENCIES);
            $lat = $randomAgency['lat'] + $faker->randomFloat(4, -0.03, 0.03);
            $lng = $randomAgency['lng'] + $faker->randomFloat(4, -0.03, 0.03);
            $agent->setCoordonnees(new Coordonnees($lat, $lng));

            $this->em->persist($agent);
            $agents[] = $agent;
        }

        $this->em->flush();
        return $agents;
    }

    private function createGerants(int $count, Role $gerantRole, $faker): array
    {
        $gerants = [];

        for ($i = 0; $i < $count; $i++) {
            $gerant = new Utilisateur(
                email: new Email('gerant' . ($i + 1) . '@mtnpdv.local'),
                nomUt: $faker->lastName(),
                prenomUt: $faker->firstName(),
                telephone: new Telephone(faker: $faker),
            );

            $gerant->setPassword($this->passwordHasher->hashPassword($gerant, 'password123'));
            $gerant->addRole($gerantRole);
            $gerant->setStatut('ACTIF');

            $this->em->persist($gerant);
            $gerants[] = $gerant;
        }

        $this->em->flush();
        return $gerants;
    }

    private function createMtnProducts(array $categories): array
    {
        $products = [];
        $categoryMap = [];

        foreach ($categories as $cat) {
            $categoryMap[$cat->getLibelle()] = $cat;
        }

        foreach (self::MTN_PRODUCTS as $productData) {
            $categorie = $categoryMap[$productData['categorie']] ?? $categories[0];

            $product = new Produit(
                nomProd: $productData['nom'],
                categorieProd: $categorie,
                prixUnitaire: new Montant($productData['prix']),
            );

            $product->setDescription('Produit/Service MTN Cameroon');
            $this->em->persist($product);
            $products[] = $product;
        }

        $this->em->flush();
        return $products;
    }

    private function createMtnAgencies(array $gerants, array $categories): array
    {
        $pdvs = [];
        $gerantIndex = 0;

        foreach (self::MTN_AGENCIES as $agencyData) {
            $pdv = new PointVente(
                nomPdv: $agencyData['nom'],
                codeRef: 'MTN-' . strtoupper(substr($agencyData['ville'], 0, 3)) . '-' . str_pad(count($pdvs) + 1, 3, '0', STR_PAD_LEFT),
                ville: $agencyData['ville'],
                adresse: $agencyData['adresse'],
                telephone: new Telephone('+237-6-' . rand(10000000, 99999999)),
                categoriePdv: $categories[rand(0, count($categories) - 1)],
            );

            $pdv->setCoordonnees(new Coordonnees($agencyData['lat'], $agencyData['lng']));
            $pdv->setGerant($gerants[$gerantIndex % count($gerants)]);
            $pdv->setStatut('ACTIF');

            $this->em->persist($pdv);
            $pdvs[] = $pdv;
            $gerantIndex++;
        }

        $this->em->flush();
        return $pdvs;
    }

    private function createSupplyFlows(int $count, array $pdvs, array $products, $faker): array
    {
        $suppliedPdvs = [];
        $now = new \DateTimeImmutable();
        $threeMonthsAgo = $now->modify('-90 days');

        for ($i = 0; $i < $count; $i++) {
            $pdv = $faker->randomElement($pdvs);
            $suppliedPdvs[$pdv->getId()] = $pdv;

            $flowDate = $faker->dateTimeBetween($threeMonthsAgo, $now);

            $flux = new FluxRavitaillement(
                pointVente: $pdv,
                dateFlux: $flowDate,
                statut: $faker->randomElement([StatutFlux::LIVRE, StatutFlux::LIVRE, StatutFlux::EN_ATTENTE]),
            );

            $numProducts = $faker->numberBetween(2, 8);
            $totalAmount = 0;

            for ($j = 0; $j < $numProducts; $j++) {
                $product = $faker->randomElement($products);
                $quantity = $faker->numberBetween(10, 200);
                $amount = $product->getPrixUnitaire()->montantCentimes * $quantity;

                $fluxProd = new FluxProduit(
                    flux: $flux,
                    produit: $product,
                    quantiteLivree: $quantity,
                );

                $this->em->persist($fluxProd);
                $totalAmount += $amount;
            }

            $flux->setMontantTotal(new Montant($totalAmount));
            $this->em->persist($flux);
        }

        $this->em->flush();
        return $suppliedPdvs;
    }

    private function createTransactions(int $count, array $agents, array $pdvs, $faker): array
    {
        $transactions = [];
        $now = new \DateTimeImmutable();
        $threeMonthsAgo = $now->modify('-90 days');

        for ($i = 0; $i < $count; $i++) {
            $agent = $faker->randomElement($agents);
            $pdv = $faker->randomElement($pdvs);

            $transactionDate = $faker->dateTimeBetween($threeMonthsAgo, $now);

            $type = $faker->randomElement([TypeTransaction::VISITE, TypeTransaction::VENTE, TypeTransaction::VENTE]);
            $montant = $type === TypeTransaction::VENTE
                ? new Montant($faker->numberBetween(50000, 500000))
                : null;

            $lat = $pdv->getCoordonnees()->getLatitude() + $faker->randomFloat(4, -0.02, 0.02);
            $lng = $pdv->getCoordonnees()->getLongitude() + $faker->randomFloat(4, -0.02, 0.02);

            $transaction = new Transaction(
                type: $type,
                montant: $montant,
                position: new Coordonnees($lat, $lng),
            );

            $transaction
                ->setPointVente($pdv)
                ->setUtilisateur($agent)
                ->setCommentaireRapport($faker->optional(0.6)->sentence());

            $statut = $faker->randomElement([
                StatutTransaction::EN_ATTENTE,
                StatutTransaction::EN_ATTENTE,
                StatutTransaction::EN_ATTENTE,
                StatutTransaction::VALIDEE,
                StatutTransaction::VALIDEE,
                StatutTransaction::REJETEE,
            ]);

            if ($statut === StatutTransaction::VALIDEE) {
                $transaction->valider();
            } elseif ($statut === StatutTransaction::REJETEE) {
                $transaction->rejeter();
            }

            $this->em->persist($transaction);
            $transactions[] = $transaction;
        }

        $this->em->flush();
        return $transactions;
    }

    private function createNotifications(array $agents, array $transactions, $faker): array
    {
        $notifications = [];

        foreach ($transactions as $transaction) {
            if ($transaction->getStatut() === StatutTransaction::VALIDEE) {
                $notif = new Notification(
                    utilisateur: $transaction->getAgent(),
                    type: TypeNotification::VISITE_VALIDEE,
                    titre: 'Visite validée',
                    message: sprintf(
                        'Votre visite à l\'agence %s (%s) a été validée par l\'administrateur.',
                        $transaction->getPointVente()->getNomPdv(),
                        $transaction->getPointVente()->getVille()
                    ),
                );

                $this->em->persist($notif);
                $notifications[] = $notif;
            } elseif ($transaction->getStatut() === StatutTransaction::REJETEE) {
                $notif = new Notification(
                    utilisateur: $transaction->getAgent(),
                    type: TypeNotification::VISITE_REJETEE,
                    titre: 'Visite rejetée',
                    message: sprintf(
                        'Votre visite à l\'agence %s (%s) a été rejetée. Veuillez contacter votre superviseur.',
                        $transaction->getPointVente()->getNomPdv(),
                        $transaction->getPointVente()->getVille()
                    ),
                );

                $this->em->persist($notif);
                $notifications[] = $notif;
            }
        }

        $this->em->flush();
        return $notifications;
    }
}
