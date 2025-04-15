<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'create_product', methods: ['POST'])]
    public function createProduct(
        Request $request,
        Security $security,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérification que l'utilisateur est authentifié et a le rôle 'ROLE_ADMIN'
        $user = $security->getUser();

        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        // Extraction des données de la requête
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $price = $data['price'] ?? null;

        if (!$name || !is_numeric($price)) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // Shopify API credentials
        $shopifyDomain = $_ENV['SHOPIFY_STORE_DOMAIN'] ?? null;
        $accessToken = $_ENV['SHOPIFY_ACCESS_TOKEN'] ?? null;

        if (!$shopifyDomain || !$accessToken) {
            return new JsonResponse(['error' => 'Shopify credentials missing'], 500);
        }

        try {
            // Envoi de la requête à l'API Shopify pour créer le produit
            $client = HttpClient::create();
            $response = $client->request('POST', "https://$shopifyDomain/admin/api/2023-07/products.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'product' => [
                        'title' => $name,
                        'variants' => [[
                            'price' => $price
                        ]]
                    ]
                ]
            ]);

            // Vérification du statut de la réponse
            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode !== 201) {
                return new JsonResponse([ 
                    'error' => 'Failed to create product on Shopify', 
                    'details' => $content 
                ], 500);
            }

            // Récupération de l'ID Shopify du produit créé
            $shopifyId = (string) $content['product']['id'];

            // Création du produit localement
            $product = new Product();
            $product->setShopifyId($shopifyId);  // Utilisation de l'ID Shopify
            $product->setCreatedBy($user);  // Utilisateur connecté
            $product->setName($name);  // Nom du produit
            $product->setPrice((float) $price);  // Prix du produit

            // Sauvegarde du produit dans la base de données
            $em->persist($product);
            $em->flush();

            // Retour d'une réponse avec succès
            return new JsonResponse([
                'message' => 'Produit créé sur Shopify et enregistré localement',
                'shopify_id' => $shopifyId
            ], 201);

        } catch (\Throwable $e) {
            // Gestion des erreurs en cas de problème avec la requête
            return new JsonResponse([ 
                'error' => 'Unexpected error', 
                'message' => $e->getMessage() 
            ], 500);
        }
    }
}
