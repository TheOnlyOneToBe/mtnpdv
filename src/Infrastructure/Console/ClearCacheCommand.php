<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ClearCacheCommand extends Command
{
    public function __construct(private string $projectDir)
    {
        parent::__construct('app:cache:clear-all');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Vide tous les caches (plus complet que cache:clear)')
            ->addOption('include-logs', null, InputOption::VALUE_NONE, 'Inclure aussi les logs')
            ->setHelp('Nettoie les caches, session, uploads temporaires');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🧹 Nettoyage complet des caches');

        $filesystem = new Filesystem();
        $includeLogs = $input->getOption('include-logs');

        $dirs = [
            'var/cache' => 'Cache application',
            'var/sessions' => 'Sessions',
            'public/uploads/temp' => 'Uploads temporaires',
        ];

        if ($includeLogs) {
            $dirs['var/log'] = 'Logs';
        }

        foreach ($dirs as $dir => $label) {
            $fullPath = "$this->projectDir/$dir";
            if (is_dir($fullPath)) {
                try {
                    $filesystem->remove($fullPath);
                    $filesystem->mkdir($fullPath);
                    $io->success("✓ $label nettoyé");
                } catch (\Exception $e) {
                    $io->warning("⚠️  Impossible de nettoyer $label: " . $e->getMessage());
                }
            }
        }

        // Vider le cache Symfony
        $io->writeln('Exécution de cache:clear...');
        $process = new Process([
            PHP_BINARY,
            'bin/console',
            'cache:clear'
        ], $this->projectDir);
        $process->run();

        $io->newLine();
        $io->success('✅ Tous les caches ont été nettoyés!');

        return Command::SUCCESS;
    }
}
