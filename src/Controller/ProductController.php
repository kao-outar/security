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
        $user = $security->getUser();

        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $price = $data['price'] ?? null;

        if (!$name || !is_numeric($price)) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $shopifyDomain = $_ENV['SHOPIFY_STORE_DOMAIN'] ?? null;
        $accessToken = $_ENV['SHOPIFY_ACCESS_TOKEN'] ?? null;

        if (!$shopifyDomain || !$accessToken) {
            return new JsonResponse(['error' => 'Shopify credentials missing'], 500);
        }

        try {
            $client = HttpClient::create();
            $response = $client->request('POST', "https://$shopifyDomain/admin/api/2023-07/products.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'product' => [
                        'title' => $name,
                        'variants' => [[ 'price' => $price ]]
                    ]
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode !== 201) {
                return new JsonResponse([
                    'error' => 'Failed to create product on Shopify',
                    'details' => $content
                ], 500);
            }

            $shopifyId = (string) $content['product']['id'];

            $product = new Product();
            $product->setShopifyId($shopifyId);
            $product->setCreatedBy($user);
            $product->setName($name);
            $product->setPrice((float) $price);

            $em->persist($product);
            $em->flush();

            return new JsonResponse([
                'message' => 'Produit créé sur Shopify et enregistré localement',
                'shopify_id' => $shopifyId
            ], 201);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Unexpected error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/products', name: 'all_products', methods: ['GET'])]
    public function getAllProducts(EntityManagerInterface $em): JsonResponse
    {
        $products = $em->getRepository(Product::class)->findAll();

        $data = array_map(function (Product $product) {
            return [
                'shopify_id' => $product->getShopifyId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'sales_count' => $product->getSalesCount(),
                'created_by' => $product->getCreatedBy()?->getUserIdentifier()
            ];
        }, $products);

        return new JsonResponse($data);
    }

    #[Route('/my-products', name: 'my_products', methods: ['GET'])]
    public function getMyProducts(Security $security, EntityManagerInterface $em): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $products = $em->getRepository(Product::class)->findBy(['createdBy' => $user]);

        $data = array_map(function (Product $product) {
            return [
                'shopify_id' => $product->getShopifyId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'sales_count' => $product->getSalesCount()
            ];
        }, $products);

        return new JsonResponse($data);
    }
}
