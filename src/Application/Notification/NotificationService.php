<?php

declare(strict_types=1);

namespace App\Application\Notification;

use App\Domain\Entity\Notification;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeNotification;
use App\Domain\Repository\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function envoyer(
        Utilisateur $utilisateur,
        TypeNotification $type,
        string $titre,
        string $message,
        ?string $lien = null,
    ): Notification {
        $notification = new Notification(
            utilisateur: $utilisateur,
            type: $type,
            titre: $titre,
            message: $message,
            lien: $lien,
        );

        $this->notificationRepository->save($notification);

        return $notification;
    }

    public function notifierVisiteValidee(Utilisateur $agent, string $pdvNom, ?string $lienVisite = null): Notification
    {
        return $this->envoyer(
            utilisateur: $agent,
            type: TypeNotification::VISITE_VALIDEE,
            titre: 'Visite validée',
            message: sprintf('Votre visite à "%s" a été validée par un administrateur.', $pdvNom),
            lien: $lienVisite,
        );
    }

    public function notifierVisiteRejetee(Utilisateur $agent, string $pdvNom, string $raison = '', ?string $lienVisite = null): Notification
    {
        $message = sprintf('Votre visite à "%s" a été rejetée.', $pdvNom);
        if (!empty($raison)) {
            $message .= sprintf(' Raison: %s', $raison);
        }

        return $this->envoyer(
            utilisateur: $agent,
            type: TypeNotification::VISITE_REJETEE,
            titre: 'Visite rejetée',
            message: $message,
            lien: $lienVisite,
        );
    }

    public function notifierVisiteCreee(Utilisateur $agent, string $pdvNom, ?string $lienVisite = null): Notification
    {
        return $this->envoyer(
            utilisateur: $agent,
            type: TypeNotification::VISITE_CREEE,
            titre: 'Visite enregistrée',
            message: sprintf('Votre visite à "%s" a été enregistrée et est en attente de validation.', $pdvNom),
            lien: $lienVisite,
        );
    }

    public function notifierGerantProduitLivre(Utilisateur $gerant, string $produitNom, int $quantite): Notification
    {
        return $this->envoyer(
            utilisateur: $gerant,
            type: TypeNotification::PRODUIT_LIVRE,
            titre: 'Produit livré',
            message: sprintf('%d unité(s) de "%s" ont été livrées à votre kiosque.', $quantite, $produitNom),
        );
    }

    public function notifierAgentApprovisionnementDemande(Utilisateur $agent, string $pdvNom, string $montant, ?string $lien = null): Notification
    {
        return $this->envoyer(
            utilisateur: $agent,
            type: TypeNotification::APPROVISIONNEMENT_DEMANDE,
            titre: 'Nouvelle demande d\'approvisionnement',
            message: sprintf('Une demande d\'approvisionnement de %s a été créée pour le point de vente "%s".', $montant, $pdvNom),
            lien: $lien,
        );
    }

    public function notifierAdminArgentRecuParAgent(Utilisateur $admin, string $agentNom, string $pdvNom, string $montant, ?string $lien = null): Notification
    {
        return $this->envoyer(
            utilisateur: $admin,
            type: TypeNotification::ARGENT_RECU_PAR_AGENT,
            titre: 'Argent reçu par l\'agent',
            message: sprintf('L\'agent %s a confirmé avoir reçu %s pour l\'approvisionnement du point de vente "%s".', $agentNom, $montant, $pdvNom),
            lien: $lien,
        );
    }

    public function notifierAgentApprovisionnementTermine(Utilisateur $agent, string $pdvNom, string $montant, ?string $lien = null): Notification
    {
        return $this->envoyer(
            utilisateur: $agent,
            type: TypeNotification::APPROVISIONNEMENT_TERMINE,
            titre: 'Approvisionnement terminé',
            message: sprintf('L\'approvisionnement de %s pour le point de vente "%s" a été marqué comme terminé.', $montant, $pdvNom),
            lien: $lien,
        );
    }

    public function notifierMessageAdmin(Utilisateur $utilisateur, string $titre, string $message): Notification
    {
        return $this->envoyer(
            utilisateur: $utilisateur,
            type: TypeNotification::MESSAGE_ADMIN,
            titre: $titre,
            message: $message,
        );
    }

    /**
     * Envoie une alerte ALERTE_SYSTEME à tous les administrateurs quand un PDV passe sous seuil.
     *
     * @return list<Notification>
     */
    public function alerterSoldeSousSeuil(
        \App\Domain\Entity\PointVente $pdv,
        bool $cashSousSeuil,
        bool $flotteSousSeuil,
        \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurs,
    ): array {
        $details = [];
        if ($cashSousSeuil) {
            $details[] = sprintf('Cash %s < seuil %s', $pdv->getSoldeCash()->toDecimal(), $pdv->getSeuilMinCash()->toDecimal());
        }
        if ($flotteSousSeuil) {
            $details[] = sprintf('Flotte %s < seuil %s', $pdv->getSoldeFlotte()->toDecimal(), $pdv->getSeuilMinFlotte()->toDecimal());
        }

        $message = sprintf(
            'Le solde du point de vente "%s" (%s) est inférieur au seuil minimal. %s',
            $pdv->getNomPdv(),
            $pdv->getVille(),
            implode(' | ', $details),
        );

        $notifications = [];
        foreach ($utilisateurs->findByRole('ADMIN') as $admin) {
            $notifications[] = $this->envoyer(
                utilisateur: $admin,
                type: TypeNotification::ALERTE_SYSTEME,
                titre: sprintf('Solde critique : %s', $pdv->getNomPdv()),
                message: $message,
            );
        }

        return $notifications;
    }

    public function marquerCommeLue(int|string $notificationId): void
    {
        $id = is_string($notificationId) ? (int) $notificationId : $notificationId;
        $notification = $this->notificationRepository->findById($id);
        if ($notification) {
            $this->notificationRepository->marquerCommeLue($notification);
        }
    }

    public function obtenirNonLuesUtilisateur(Utilisateur $utilisateur, int $limit = 10): array
    {
        return $this->notificationRepository->findNonLuesParUtilisateur($utilisateur, $limit);
    }

    public function compterNonLuesUtilisateur(Utilisateur $utilisateur): int
    {
        return $this->notificationRepository->compterNonLues($utilisateur);
    }
}
