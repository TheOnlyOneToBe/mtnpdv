<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Doctrine\DBAL\Connection;

class SetupProjectCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct('app:setup');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Configure le projet (installe dépendances, crée DB, setup uploads)')
            ->setHelp('Cette commande installe les dépendances et configure le projet complet');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🚀 Configuration du projet MTNPDV');

        // 1. Installer les dépendances Composer
        $io->section('1️⃣  Installation des dépendances Composer');
        if ($this->commandExists('composer')) {
            $process = new Process(['composer', 'install', '--optimize-autoloader']);
            $process->run();
            $io->success('Dépendances PHP installées ✅');
        } else {
            $io->warning('Composer n\'est pas disponible - installation PHP ignorée');
        }

        // 2. Installer les dépendances npm
        $io->section('2️⃣  Installation des dépendances npm');
        if ($this->commandExists('npm')) {
            $process = new Process(['npm', 'install']);
            $process->run();
            $io->success('Dépendances npm installées ✅');
        } else {
            $io->note('npm n\'est pas disponible - dépendances npm ignorées');
        }

        // 3. Créer/mettre à jour la base de données
        $io->section('3️⃣  Création de la base de données');
        try {
            $this->connection->executeStatement('SELECT 1');
            $io->note('Base de données existante détectée');
        } catch (\Exception $e) {
            $io->writeln('Création de la base de données...');
            $process = new Process([
                PHP_BINARY,
                'bin/console',
                'doctrine:database:create',
                '--if-not-exists'
            ]);
            $process->run();
        }

        // 4. Exécuter les migrations
        $io->writeln('Exécution des migrations...');
        $process = new Process([
            PHP_BINARY,
            'bin/console',
            'doctrine:migrations:migrate',
            '--no-interaction'
        ]);
        $process->run();
        $io->success('Base de données configurée ✅');

        // 5. Créer les dossiers de uploads
        $io->section('4️⃣  Création des dossiers de stockage');
        $uploadDirs = [
            'public/uploads/profils',
            'public/uploads/preuves',
            'public/uploads/pos',
            'var/cache',
            'var/log'
        ];

        foreach ($uploadDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $io->writeln("✓ Créé: <fg=green>$dir</>");
            }
        }
        $io->success('Dossiers de stockage créés ✅');

        // 6. Construire les assets si npm existe
        $io->section('5️⃣  Construction des assets');
        if ($this->commandExists('npm')) {
            $process = new Process(['npm', 'run', 'build']);
            $process->run();
            if ($process->isSuccessful()) {
                $io->success('Assets construits ✅');
            } else {
                $io->note('Build npm n\'a pas pu être exécuté - développement seulement');
            }
        } else {
            $io->note('npm non disponible - assets non construits');
        }

        // Résumé final
        $io->newLine();
        $io->success('Configuration terminée avec succès! 🎉');
        $io->newLine();

        $io->section('📋 Prochaines étapes');
        $io->listing([
            'Créer le premier utilisateur admin: <fg=cyan>php bin/console app:utilisateur:creer</>',
            'Lancer le serveur: <fg=cyan>symfony serve</>',
            'Accéder à l\'app: <fg=cyan>http://localhost:8000</>',
            'Lancer les tests: <fg=cyan>php bin/phpunit</>'
        ]);

        return Command::SUCCESS;
    }

    private function commandExists(string $command): bool
    {
        $process = new Process(['which', $command]);
        $process->run();
        return $process->isSuccessful();
    }
}
