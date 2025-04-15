#!/usr/bin/env php
<?php
// Script CLI pour générer la signature HMAC Shopify depuis un fichier

if ($argc < 3) {
    echo "Usage: php generate_shopify_hmac.php <secret> <payload_file>\n";
    exit(1);
}

$secret = $argv[1];
var_dump($secret);  
$payload = file_get_contents($argv[2]);

$hmac = base64_encode(hash_hmac('sha256', $payload, $secret, true));

echo "Signature HMAC : $hmac\n"; 