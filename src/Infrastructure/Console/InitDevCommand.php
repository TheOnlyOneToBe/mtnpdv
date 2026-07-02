<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class InitDevCommand extends Command
{
    public function __construct()
    {
        parent::__construct('app:dev:init');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Initialise l\'environnement de développement complet')
            ->setHelp('Équivalent à setup mais avec données de test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🚀 Initialisation du développement');

        $steps = [
            ['app:setup', 'Configuration du projet'],
            ['app:db:reset', 'Réinitialisation de la BD (dev)'],
            ['app:fixtures:generate', 'Génération des données de test'],
            ['app:health', 'Vérification de la santé'],
        ];

        foreach ($steps as [$command, $label]) {
            $io->section($label);
            $process = new Process([PHP_BINARY, 'bin/console', $command, '-n']);
            $process->setTty(true);
            $process->run();

            if (!$process->isSuccessful()) {
                $io->warning("⚠️  $command n'a pas pu être exécutée");
            }
        }

        $io->newLine();
        $io->success('✅ Environnement de développement prêt!');
        $io->listing([
            'Lancer le serveur: <fg=cyan>symfony serve</>',
            'Voir l\'app: <fg=cyan>http://localhost:8000</>',
            'Utilisateur: <fg=cyan>admin.test@example.com / AdminTest123!</>'
        ]);

        return Command::SUCCESS;
    }
}
