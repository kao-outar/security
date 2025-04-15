<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ShopifyWebhookController extends AbstractController
{
    #[Route('/webhooks/shopify-sales', name: 'shopify_sales_webhook', methods: ['POST'])]
    public function handleShopifySalesWebhook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $hmacHeader = $request->headers->get('X-Shopify-Hmac-Sha256');
        $webhookSecret = getenv('SHOPIFY_WEBHOOK_SECRET') ?: ($_ENV['SHOPIFY_WEBHOOK_SECRET'] ?? null);
        $rawBody = $request->getContent();

        // Vérification de la signature HMAC
        if (!$webhookSecret || !$hmacHeader) {
            return new JsonResponse(['error' => 'Signature ou secret manquant'], 401);
        }
        $calculatedHmac = base64_encode(hash_hmac('sha256', $rawBody, $webhookSecret, true));
        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            return new JsonResponse(['error' => 'Signature HMAC invalide'], 401);
        }

        // Décodage du payload
        $data = json_decode($rawBody, true);
        if (!$data || !isset($data['line_items'])) {
            return new JsonResponse(['error' => 'Payload invalide'], 400);
        }

        $repo = $em->getRepository(Product::class);
        $updated = [];
        foreach ($data['line_items'] as $item) {
            $shopifyId = (string)($item['product_id'] ?? null);
            $quantity = (int)($item['quantity'] ?? 0);
            if (!$shopifyId || $quantity <= 0) continue;

            $product = $repo->find($shopifyId);
            if ($product) {
                $product->setSalesCount($product->getSalesCount() + $quantity);
                $updated[] = $shopifyId;
            }
        }
        $em->flush();

        return new JsonResponse([
            'message' => 'Webhook traité',
            'produits_mis_a_jour' => $updated
        ], 200);
    }
} 