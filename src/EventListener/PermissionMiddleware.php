<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\SecurityBundle\Security; // ✅ ici
use App\Entity\User;

class PermissionMiddleware
{   
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if ($path === '/users' && !$user->getRole()?->isCanGetUsers()) {
            throw new AccessDeniedHttpException("Tu n'as pas le droit de voir les utilisateurs 😡");
        }

        if ($path === '/my-user' && !$user->getRole()?->isCanGetMyUser()) {
            throw new AccessDeniedHttpException("Tu ne peux pas voir ton propre profil 😢");
        }

        if ($path === '/login' && !$user->getRole()?->isCanPostLogin()) {
            throw new AccessDeniedHttpException("Tu ne peux pas te connecter 😶");
        }
    }
}
