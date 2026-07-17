<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\ValueObject\Montant;

interface TransactionRepositoryInterface
{
    public function find(int $id): ?Transaction;

    /** @return list<Transaction> */
    public function findAll(): array;

    /** @return list<Transaction> */
    public function findByPointVente(PointVente $pointVente): array;

    /** @return list<Transaction> */
    public function findByUtilisateur(Utilisateur $utilisateur): array;

    /** @return list<Transaction> */
    public function findByType(TypeTransaction $type): array;

    /** @return list<Transaction> */
    public function findByStatut(StatutTransaction $statut): array;

    /** @return list<Transaction> */
    public function findEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array;

    /** @return list<int> IDs des PDV ayant reçu une visite sur la période */
    public function findPdvIdsVisitesEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array;

    /** @return list<Transaction> */
    public function findVisitesEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array;

    /** @return list<Transaction> */
    public function findVisitesParAgent(Utilisateur $agent, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array;

    /**
     * @return list<Transaction>
     */
    public function findByFiltres(
        ?TypeTransaction $type = null,
        ?PointVente $pointVente = null,
        ?Utilisateur $agent = null,
        ?\DateTimeImmutable $debut = null,
        ?\DateTimeImmutable $fin = null,
        ?Montant $montantMin = null,
        ?Montant $montantMax = null,
    ): array;

    /**
     * Chiffre d'affaires (ventes validées) d'un point de vente, éventuellement borné dans le temps.
     */
    public function chiffreAffaires(
        PointVente $pointVente,
        ?\DateTimeImmutable $debut = null,
        ?\DateTimeImmutable $fin = null,
    ): Montant;

    public function save(Transaction $transaction, bool $flush = true): void;

    public function remove(Transaction $transaction, bool $flush = true): void;
}
