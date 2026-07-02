<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

class ListRoutesCommand extends Command
{
    public function __construct(private RouterInterface $router)
    {
        parent::__construct('app:routes:list');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Liste toutes les routes triées par contrôleur')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filtrer par nom ou contrôleur')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'Filtrer par rôle requis')
            ->setHelp('Affiche toutes les routes disponibles de manière lisible');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filter = $input->getOption('filter');
        $roleFilter = $input->getOption('role');

        $io->title('📍 Routes de l\'application');

        $routes = $this->router->getRouteCollection();
        $grouped = [];

        foreach ($routes as $name => $route) {
            if ($filter && !str_contains($name, $filter) && !str_contains($route->getPath(), $filter)) {
                continue;
            }

            $controller = $route->getDefault('_controller') ?? 'N/A';
            $methods = $route->getMethods() ?: ['GET'];
            $path = $route->getPath();

            // Extraire le contrôleur
            if (is_string($controller)) {
                [$controllerClass] = explode('::', $controller);
                $parts = explode('\\', $controllerClass);
                $controllerName = end($parts);
            } else {
                $controllerName = 'Callable';
            }

            if (!isset($grouped[$controllerName])) {
                $grouped[$controllerName] = [];
            }

            $grouped[$controllerName][] = [
                'name' => $name,
                'methods' => implode('|', $methods),
                'path' => $path,
                'controller' => $controller,
            ];
        }

        // Afficher les routes groupées
        foreach ($grouped as $controllerName => $routes) {
            $io->section($controllerName);

            $table = $io->createTable();
            $table->setHeaders(['Nom', 'Méthode', 'Chemin']);

            foreach ($routes as $route) {
                $table->addRow([
                    $route['name'],
                    $route['methods'],
                    $route['path'],
                ]);
            }

            $table->render();
        }

        $io->newLine();
        $io->info("Total: " . count($this->router->getRouteCollection()) . " routes");

        return Command::SUCCESS;
    }
}
