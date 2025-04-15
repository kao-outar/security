<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class JWTUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->getRole()?->isCanPostLogin()) {
            throw new CustomUserMessageAccountStatusException("Ce compte est banni ou désactivé.");
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // rien ici pour le moment
    }
}
