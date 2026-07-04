<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Domain\Entity\Role;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

#[AsCommand(
    name: 'app:init:database',
    description: 'Initialize database schema and optionally seed fake data',
)]
class InitDatabaseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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
                'Generate fake data for development (will ask for confirmation if not provided)',
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

        $io->title('🚀 MTNPDV - Database Initialization');
        $io->writeln('📍 MTN Cameroon - Points of Sale Management System');

        try {
            // Step 0: Run migrations
            $io->section('Step 0: Creating database schema');
            $this->runMigrations($io);
            $io->success('Database schema created');

            // Step 1: Clear database
            $io->section('Step 1: Clearing database');
            if (!$input->getOption('force')) {
                if (!$io->confirm('Are you sure you want to delete ALL data?', false)) {
                    $io->warning('Operation cancelled');
                    return Command::FAILURE;
                }
            }
            $this->clearDatabase();
            $io->success('Database cleared');

            // Step 2: Create base roles
            $io->section('Step 2: Creating base roles');
            $this->createRoles();
            $io->success('Created 3 base roles (ADMIN, AGENT, GERANT)');

            // Step 3: Ask about fake data
            $withData = $input->getOption('with-data');
            if (!$withData && !$input->getOption('no-data') && !$input->getOption('force')) {
                $withData = $io->confirm('Generate fake data for development?', false);
            }

            if ($withData && !$input->getOption('no-data')) {
                $io->section('Step 3: Generating fake data');
                $this->seedFakeData($input, $output, $io);
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

    private function runMigrations(SymfonyStyle $io): void
    {
        try {
            $connection = $this->em->getConnection();
            $platform = $connection->getDatabasePlatform();

            // Step 1: Create database if it doesn't exist
            try {
                if ($platform instanceof MySQLPlatform) {
                    $this->createMySQLDatabase($io);
                } elseif ($platform instanceof SQLitePlatform) {
                    $io->info('SQLite database will be created automatically');
                }
            } catch (\Exception $e) {
                $io->warning('Database creation: ' . $e->getMessage());
            }

            // Step 2: Ensure connection is fresh after database creation
            try {
                $connection->close();
                $connection->connect();
            } catch (\Exception) {
                // Connection might already be open, ignore
            }

            // Step 3: Disable foreign key checks
            if ($platform instanceof MySQLPlatform) {
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            } elseif ($platform instanceof SQLitePlatform) {
                $connection->executeStatement('PRAGMA foreign_keys=OFF');
            }

            // Step 4: Create schema from entities using SchemaTool
            $schemaTool = new SchemaTool($this->em);
            $metadataFactory = $this->em->getMetadataFactory();
            $allMetadata = $metadataFactory->getAllMetadata();

            try {
                $schemaTool->dropDatabase();
                $io->info('Existing database schema dropped');
            } catch (\Exception) {
                $io->info('No existing schema to drop');
            }

            try {
                $schemaTool->createSchema($allMetadata);
                $io->success('Database schema created from entities');
            } catch (\Exception $e) {
                $io->error('Schema creation failed: ' . $e->getMessage());
                throw $e;
            }

            // Step 5: Re-enable foreign keys
            if ($platform instanceof MySQLPlatform) {
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($platform instanceof SQLitePlatform) {
                $connection->executeStatement('PRAGMA foreign_keys=ON');
            }
        } catch (\Exception $e) {
            $io->error('Failed to initialize database: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createMySQLDatabase(SymfonyStyle $io): void
    {
        $connection = $this->em->getConnection();
        $params = $connection->getParams();

        // Get database name from connection parameters
        $dbName = $params['dbname'] ?? null;

        if (!$dbName) {
            throw new \RuntimeException('Database name not configured');
        }

        // Create a temporary connection without specifying database
        $tmpParams = $params;
        unset($tmpParams['dbname']);

        try {
            // Use root or admin connection to create database
            $tmpConnection = DriverManager::getConnection($tmpParams);

            // Create database if it doesn't exist
            $sql = sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $dbName);
            $tmpConnection->executeStatement($sql);

            $io->info("Database `{$dbName}` created/verified");

            $tmpConnection->close();
        } catch (\Exception $e) {
            $io->warning("Could not create database: " . $e->getMessage());
            // Continue anyway, SchemaTool might handle it
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
            '`transaction`',
            'utilisateur_role',
            'point_vente',
            'utilisateur',
            'produit',
            'categorie_prod',
            'categorie_pdv',
            'role',
        ];

        foreach ($tables as $table) {
            try {
                $connection->executeStatement($platform->getTruncateTableSQL($table, true));
            } catch (\Exception) {
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

    private function createRoles(): void
    {
        $roleDefinitions = [
            'ADMIN' => 'Administrateur système',
            'AGENT' => 'Agent terrain',
            'GERANT' => 'Gérant de point de vente MTN',
        ];

        foreach ($roleDefinitions as $code => $libelle) {
            $role = new Role($code, $libelle);
            $this->em->persist($role);
        }

        $this->em->flush();
    }

    private function seedFakeData(InputInterface $input, OutputInterface $output, SymfonyStyle $io): void
    {
        // Call the dedicated seeder command
        $command = $this->getApplication()->find('app:seed:faker');

        $commandInput = new ArrayInput([
            '--no-interaction' => true,
        ]);

        try {
            $returnCode = $command->run($commandInput, $output);
            if ($returnCode !== Command::SUCCESS) {
                $io->error('Failed to seed fake data');
            }
        } catch (\Exception $e) {
            $io->error('Error seeding fake data: ' . $e->getMessage());
        }
    }
}
