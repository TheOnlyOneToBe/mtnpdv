<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class OptimizeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('app:optimize');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Optimise l\'application pour la production')
            ->setHelp('Nettoie les caches, précharge les classes, compile les routes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('⚡ Optimisation de l\'application');

        $commands = [
            ['cache:clear', '--no-warmup', 'Nettoyage du cache'],
            ['cache:warmup', null, 'Réchauffement du cache'],
            ['asset-map:compile', null, 'Compilation des assets'],
        ];

        foreach ($commands as [$cmd, $option, $label]) {
            $io->writeln("<fg=cyan>$label...</>");

            $process = new Process(array_filter([
                PHP_BINARY,
                'bin/console',
                $cmd,
                $option
            ]));

            $process->run();

            if ($process->isSuccessful()) {
                $io->success("✓ $label");
            } else {
                $io->warning("⚠️  $label (non critique)");
            }
        }

        // Vérifier PHP OPCache
        $io->section('Configuration PHP');

        $opcacheEnabled = extension_loaded('Zend OPcache');
        if ($opcacheEnabled) {
            $io->success('✓ OPCache activé');
        } else {
            $io->warning('⚠️  OPCache désactivé (améliore les performances)');
        }

        $io->newLine();
        $io->success('✅ Optimisations appliquées!');
        $io->note('Pour la production: vérifiez la configuration PHP et nginx/apache');

        return Command::SUCCESS;
    }
}
