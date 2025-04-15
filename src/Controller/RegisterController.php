<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Missing fields'], 400);
        }

        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_DEFAULT));

        // Par défaut, rôle USER
        $role = $em->getRepository(Role::class)->findOneBy(['name' => 'USER']);

        // Si un admin est connecté et fournit un role_id, on l'utilise
        $currentUser = $this->getUser();
        //var_dump($currentUser);
        if ($currentUser && in_array('ROLE_ADMIN', $currentUser->getRoles()) && isset($data['role_id'])) {
            $roleCandidat = $em->getRepository(Role::class)->find($data['role_id']);
            if ($roleCandidat) {
                $role = $roleCandidat;
            }
        }

        if (!$role) {
            return new JsonResponse(['error' => 'Le rôle est introuvable en base.'], 500);
        }
        $user->setRole($role);

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['status' => 'User created']);
    }
}
