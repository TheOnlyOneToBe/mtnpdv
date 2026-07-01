<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crée un utilisateur avec ses rôles — sert notamment à créer le premier administrateur :
 * php bin/console app:utilisateur:creer admin@exemple.com --role=ADMIN
 */
#[AsCommand(
    name: 'app:utilisateur:creer',
    description: 'Crée un utilisateur (nom, prénom, email, téléphone, mot de passe, rôles)',
)]
final class CreerUtilisateurCommand extends Command
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly RoleRepositoryInterface $roles,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail (identifiant de connexion)')
            ->addOption('nom', null, InputOption::VALUE_REQUIRED, 'Nom de famille', 'Utilisateur')
            ->addOption('prenom', null, InputOption::VALUE_REQUIRED, 'Prénom', 'Nouveau')
            ->addOption('telephone', null, InputOption::VALUE_REQUIRED, 'Numéro de téléphone', '+237600000000')
            ->addOption('mot-de-passe', null, InputOption::VALUE_REQUIRED, 'Mot de passe en clair (sera haché) ; demandé interactivement sinon')
            ->addOption('role', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Code de rôle à assigner (répétable), ex. ADMIN', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $email = Email::fromString($input->getArgument('email'));
            $telephone = Telephone::fromString($input->getOption('telephone'));
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        if (null !== $this->utilisateurs->findOneByEmail($email)) {
            $io->error(sprintf('Un utilisateur avec l\'adresse "%s" existe déjà.', $email));

            return Command::FAILURE;
        }

        $motDePasse = $input->getOption('mot-de-passe')
            ?? $io->askHidden('Mot de passe', static function (?string $valeur): string {
                if (null === $valeur || \strlen($valeur) < 8) {
                    throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
                }

                return $valeur;
            });

        if (null === $motDePasse) {
            $io->error('Aucun mot de passe fourni.');

            return Command::INVALID;
        }

        $utilisateur = new Utilisateur(
            $input->getOption('nom'),
            $input->getOption('prenom'),
            $email,
            'temporaire',
            $telephone,
        );
        $utilisateur->setMotPass($this->hasher->hashPassword($utilisateur, $motDePasse));

        foreach ($input->getOption('role') as $codeRole) {
            $role = $this->roles->findOneByCode($codeRole);

            if (null === $role) {
                $io->error(sprintf('Rôle inconnu : "%s". Exécutez les migrations pour créer les rôles de base.', $codeRole));

                return Command::FAILURE;
            }

            $utilisateur->addRole($role);
        }

        $this->utilisateurs->save($utilisateur);

        $io->success(sprintf(
            'Utilisateur %s <%s> créé avec les rôles : %s',
            $utilisateur->getNomComplet(),
            $utilisateur->getUserIdentifier(),
            implode(', ', $utilisateur->getRoles()),
        ));

        return Command::SUCCESS;
    }
}
