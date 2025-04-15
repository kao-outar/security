<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class JWTAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
        private UserCheckerInterface $userChecker
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('No JWT token provided');
        }

        $token = substr($authHeader, 7);
        $data = $this->jwtManager->parse($token);

        if (!$data || !isset($data['username'])) {
            throw new AuthenticationException('Invalid token');
        }

        $user = $this->userRepository->findOneBy(['email' => $data['username']]);

        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        // 🔥 Bloque la connexion si le rôle n’a pas le droit de se connecter
        $role = $user->getRole();
        if (!$role || !$role->isCanPostLogin()) {
            throw new AuthenticationException("Ce compte est banni ou n’a pas l’autorisation de se connecter.");
        }

        $this->userChecker->checkPreAuth($user);

        return new SelfValidatingPassport($user);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['error' => 'Unauthorized: ' . $exception->getMessage()], 401);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // continue la requête normalement
    }
}
