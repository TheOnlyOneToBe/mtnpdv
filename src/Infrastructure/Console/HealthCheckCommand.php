<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;

class HealthCheckCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct('app:health');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Vérifie la santé de l\'application')
            ->setHelp('Contrôle: BD, fichiers, permissions, config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();
        $checks = [];

        $io->title('🏥 Vérification de la santé de l\'application');

        // 1. Vérifier la base de données
        $io->writeln('Vérification de la base de données...');
        try {
            $this->connection->executeStatement('SELECT 1');
            $checks['Base de données'] = ['✅', 'Connectée'];
        } catch (\Exception $e) {
            $checks['Base de données'] = ['❌', 'Erreur: ' . $e->getMessage()];
        }

        // 2. Vérifier les dossiers
        $io->writeln('Vérification des dossiers...');
        $uploadDirs = [
            'public/uploads/profils',
            'public/uploads/preuves',
            'public/uploads/pos',
            'var/cache',
            'var/log'
        ];

        foreach ($uploadDirs as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                $checks["Dossier: $dir"] = ['✅', 'Accessible'];
            } else {
                $checks["Dossier: $dir"] = ['❌', 'Non accessible'];
            }
        }

        // 3. Vérifier les fichiers de config
        $io->writeln('Vérification de la configuration...');
        $files = ['.env', '.env.local', '.env.test', 'composer.json', 'package.json'];
        foreach ($files as $file) {
            if (file_exists($file)) {
                $checks["Fichier: $file"] = ['✅', 'Trouvé'];
            } else {
                $checks["Fichier: $file"] = ['⚠️ ', 'Manquant'];
            }
        }

        // 4. Vérifier PHP
        $io->writeln('Vérification de PHP...');
        $checks['PHP Version'] = ['✅', phpversion()];
        $checks['Memory Limit'] = ['✅', ini_get('memory_limit')];
        $checks['Max Upload'] = ['✅', ini_get('upload_max_filesize')];

        // Afficher les résultats
        $io->newLine();
        $table = $io->createTable();
        $table->setHeaders(['Composant', 'Statut', 'Détail']);

        foreach ($checks as $component => [$status, $detail]) {
            $table->addRow([$component, $status, $detail]);
        }

        $table->render();

        // Résumé
        $io->newLine();
        $errors = array_filter($checks, fn($check) => str_starts_with($check[0], '❌'));

        if (empty($errors)) {
            $io->success('✅ Tous les vérifications sont OK!');
            return Command::SUCCESS;
        } else {
            $io->warning('⚠️  ' . count($errors) . ' problème(s) détecté(s)');
            return Command::FAILURE;
        }
    }
}
