<?php

declare(strict_types=1);

namespace App\Application\Session;

use App\Domain\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SessionLockService
{
    private const SESSION_LOCK_KEY = '_session_lock';
    private const SESSION_LAST_ACTIVITY = '_last_activity';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly int $inactivityTimeoutSeconds = 1800,
    ) {
    }

    public function updateLastActivity(SessionInterface $session): void
    {
        $session->set(self::SESSION_LAST_ACTIVITY, time());
    }

    public function lockSession(
        SessionInterface $session,
        ?string $pageUrl = null,
        ?string $pageState = null
    ): void {
        $session->set(self::SESSION_LOCK_KEY, [
            'isLocked' => true,
            'pageUrl' => $pageUrl,
            'pageState' => $pageState,
            'dateLocked' => time(),
        ]);
    }

    public function unlockSession(SessionInterface $session, Utilisateur $utilisateur, string $password): bool
    {
        if (!$this->passwordHasher->isPasswordValid($utilisateur, $password)) {
            return false;
        }

        $session->set(self::SESSION_LOCK_KEY, [
            'isLocked' => false,
            'pageUrl' => null,
            'pageState' => null,
            'dateLocked' => null,
        ]);

        $this->updateLastActivity($session);

        return true;
    }

    public function isSessionLocked(SessionInterface $session): bool
    {
        $lockData = $session->get(self::SESSION_LOCK_KEY);

        return $lockData && $lockData['isLocked'] === true;
    }

    public function checkInactivityTimeout(SessionInterface $session): bool
    {
        $lastActivity = $session->get(self::SESSION_LAST_ACTIVITY);

        if (!$lastActivity) {
            $this->updateLastActivity($session);
            return false;
        }

        $elapsedTime = time() - $lastActivity;

        if ($elapsedTime > $this->inactivityTimeoutSeconds && !$this->isSessionLocked($session)) {
            $this->lockSession($session);
            return true;
        }

        return false;
    }

    public function getSessionLockState(SessionInterface $session): array
    {
        $lockData = $session->get(self::SESSION_LOCK_KEY, []);
        $lastActivity = $session->get(self::SESSION_LAST_ACTIVITY);
        $elapsedTime = $lastActivity ? time() - $lastActivity : 0;
        $remainingSeconds = max(0, $this->inactivityTimeoutSeconds - $elapsedTime);

        return [
            'isLocked' => $lockData['isLocked'] ?? false,
            'pageUrl' => $lockData['pageUrl'] ?? null,
            'pageState' => $lockData['pageState'] ?? null,
            'dateLocked' => isset($lockData['dateLocked']) && $lockData['dateLocked'] ? date('c', $lockData['dateLocked']) : null,
            'timeoutSeconds' => $this->inactivityTimeoutSeconds,
            'remainingSeconds' => $remainingSeconds,
        ];
    }

    public function getInactivityTimeoutSeconds(): int
    {
        return $this->inactivityTimeoutSeconds;
    }
}
