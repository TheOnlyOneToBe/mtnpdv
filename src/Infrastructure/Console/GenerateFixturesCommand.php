<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Domain\Entity\Utilisateur;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Role;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\Enum\StatutUtilisateur;
use App\Domain\Enum\StatutPointVente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GenerateFixturesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct('app:fixtures:generate');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Génère des données de test')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Nombre de PDV à créer', 10)
            ->addOption('agents', 'a', InputOption::VALUE_OPTIONAL, 'Nombre d\'agents à créer', 5)
            ->setHelp('Crée des utilisateurs et points de vente pour les tests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🌱 Génération des données de test');

        $pdvCount = (int) $input->getOption('count');
        $agentCount = (int) $input->getOption('agents');

        try {
            // Récupérer les rôles existants, ou les créer s'ils n'existent pas
            $roleAdmin = $this->entityManager->getRepository(Role::class)->findOneBy(['codeRole' => 'ADMIN']);
            if (!$roleAdmin) {
                $roleAdmin = new Role('ADMIN', 'Administrateur');
                $this->entityManager->persist($roleAdmin);
            }

            $roleAgent = $this->entityManager->getRepository(Role::class)->findOneBy(['codeRole' => 'AGENT']);
            if (!$roleAgent) {
                $roleAgent = new Role('AGENT', 'Agent de Terrain');
                $this->entityManager->persist($roleAgent);
            }

            $roleGerant = $this->entityManager->getRepository(Role::class)->findOneBy(['codeRole' => 'GERANT']);
            if (!$roleGerant) {
                $roleGerant = new Role('GERANT', 'Gérant de Point de Vente');
                $this->entityManager->persist($roleGerant);
            }


            // Créer un admin de test
            $io->section('Création d\'un Admin de test');
            $admin = new Utilisateur(
                nomUt: 'Admin',
                prenomUt: 'Test',
                email: Email::fromString('admin.test@example.com'),
                motPassHache: '',
                telephone: Telephone::fromString('+237123456789')
            );
            $admin->addRole($roleAdmin);
            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'AdminTest123!');
            $admin->setPassword($hashedPassword);
            $this->entityManager->persist($admin);
            $io->success("✓ Admin créé: admin.test@example.com");

            // Créer des agents
            $io->section("Création de $agentCount Agents");
            for ($i = 1; $i <= $agentCount; $i++) {
                $agent = new Utilisateur(
                    nomUt: "Agent$i",
                    prenomUt: "Test$i",
                    email: Email::fromString("agent$i@example.com"),
                    motPassHache: '',
                    telephone: Telephone::fromString(sprintf('+237%08d', 123456789 + $i))
                );
                $agent->addRole($roleAgent);
                $hashedPassword = $this->passwordHasher->hashPassword($agent, 'Agent123!');
                $agent->setPassword($hashedPassword);
                $this->entityManager->persist($agent);
            }
            $io->success("✓ $agentCount agents créés");

            // Créer des gérants et PDV
            $io->section("Création de $pdvCount Points de Vente");
            $cities = ['Douala', 'Yaoundé', 'Buea', 'Bamenda', 'Garoua'];

            for ($i = 1; $i <= $pdvCount; $i++) {
                $gerant = new Utilisateur(
                    nomUt: "Gerant$i",
                    prenomUt: "Test$i",
                    email: Email::fromString("gerant$i@example.com"),
                    motPassHache: '',
                    telephone: Telephone::fromString(sprintf('+237%08d', 900000000 + $i))
                );
                $gerant->addRole($roleGerant);
                $hashedPassword = $this->passwordHasher->hashPassword($gerant, 'Gerant123!');
                $gerant->setPassword($hashedPassword);
                $this->entityManager->persist($gerant);

                $city = $cities[$i % count($cities)];
                $coords = $this->randomCoordinates();
                $pdvTelephone = Telephone::fromString(sprintf('+237%08d', 600000000 + $i));

                $pdv = new PointVente(
                    nomPdv: "PDV-$city-$i",
                    codeRef: "Kiosque Test $i - $city",
                    coordonnees: $coords,
                    ville: $city,
                    telephone: $pdvTelephone
                );
                $pdv->setGerant($gerant);
                $pdv->setStatutActuel(StatutPointVente::ACTIF);

                $this->entityManager->persist($pdv);

                if ($i % 5 === 0) {
                    $io->writeln("  ✓ $i/$pdvCount points de vente créés...");
                }
            }

            $this->entityManager->flush();

            $io->newLine();
            $io->success('✅ Données de test générées avec succès!');
            $io->note("Admin: admin.test@example.com / AdminTest123!");
            $io->note("Agents: agent1@example.com / Agent123! (à agent$agentCount)");
            $io->note("Gérants: gerant1@example.com / Gerant123! (à gerant$pdvCount)");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function randomCoordinates(): Coordonnees
    {
        // Coordonnées aléatoires en Afrique centrale
        $lat = 3.0 + (mt_rand(-20, 40) / 100);
        $lng = 9.0 + (mt_rand(-20, 40) / 100);
        return new Coordonnees($lat, $lng);
    }
}
