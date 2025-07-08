<?php
// Settings
$privateKeyFile = 'private.pem';
$publicKeyFile = 'public.pem';
$keyBits = 2048;

// Generate private key
$privateKey = openssl_pkey_new([
    "private_key_bits" => $keyBits,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
]);

if (!$privateKey) {
    die("❌ Failed to generate private key\n");
}

// Export private key to file
openssl_pkey_export($privateKey, $privateKeyPem);
file_put_contents($privateKeyFile, $privateKeyPem);
echo "✅ Private key saved to $privateKeyFile\n";

// Extract public key from private key
$keyDetails = openssl_pkey_get_details($privateKey);
$publicKeyPem = $keyDetails['key'];
file_put_contents($publicKeyFile, $publicKeyPem);
echo "✅ Public key saved to $publicKeyFile\n";
