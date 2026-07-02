<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Doctrine\DBAL\Connection;

class ResetDatabaseCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct('app:db:reset');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Réinitialise la base de données (DEV uniquement)')
            ->setHelp('Supprime et recrée la base de données, puis exécute les migrations')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Ne pas demander de confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $noInteraction = $input->getOption('no-interaction');

        $io->warning('⚠️  Cette commande supprimera TOUTES les données de la base de données!');

        if (!$noInteraction && !$io->confirm('Êtes-vous sûr(e) de vouloir continuer?')) {
            $io->info('Opération annulée.');
            return Command::SUCCESS;
        }

        $io->section('Réinitialisation de la base de données');

        try {
            $io->writeln('1️⃣  Suppression de la base de données...');
            $this->connection->executeStatement('DROP DATABASE IF EXISTS mtnpdv');
            $io->success('✓ Base de données supprimée');

            $io->writeln('2️⃣  Création de la base de données...');
            $this->connection->executeStatement('CREATE DATABASE mtnpdv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $io->success('✓ Base de données créée');

            $io->writeln('3️⃣  Exécution des migrations...');
            $process = new Process([
                PHP_BINARY,
                'bin/console',
                'doctrine:migrations:migrate',
                '--no-interaction'
            ]);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception('Les migrations ont échoué');
            }
            $io->success('✓ Migrations exécutées');

            $io->newLine();
            $io->success('✅ Base de données réinitialisée avec succès!');
            $io->note('Prochaine étape: php bin/console app:utilisateur:creer');

        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
