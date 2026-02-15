<?php

namespace ZATCA\Signing;

use InvalidArgumentException;
use phpseclib3\Crypt\EC;
use RuntimeException;

class ECDSA
{
    public static function sign(string $content, $privateKey = null): array
    {
        $key = self::parsePrivateKey($privateKey, null, true);

        if (!openssl_sign($content, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign content');
        }

        return [
            'base64' => base64_encode($signature),
            'bytes' => $signature,
        ];
    }

    private static function addHeaderBlocks(string $keyContent): string
    {
        $header = "-----BEGIN EC PRIVATE KEY-----";
        $footer = "-----END EC PRIVATE KEY-----";

        if (strpos($keyContent, $header) === false || strpos($keyContent, $footer) === false) {
            $keyContent = $header . "\n" . trim($keyContent) . "\n" . $footer;
        }

        return $keyContent;
    }

    private static function readPrivateKeyFromPem(string $pem): \OpenSSLAsymmetricKey
    {
        $key = openssl_pkey_get_private(self::addHeaderBlocks($pem));
        if ($key === false) {
            throw new RuntimeException('Unable to parse private key');
        }
        return $key;
    }

    private static function parsePrivateKey($key = null): \OpenSSLAsymmetricKey
    {
        $parsedKey = null;

        if ($key instanceof \OpenSSLAsymmetricKey) {
            $parsedKey = $key;
        } elseif (is_string($key)) {
            $content = base64_decode($key);
            $parsedKey = self::readPrivateKeyFromPem($content);
        }

        if (!$parsedKey) {
            throw new InvalidArgumentException('privateKey or privateKeyPath is required');
        }

        return $parsedKey;
    }

    public static function ecSecp256k1PrivKey(): string
    {
        // openssl ecparam -name secp256k1 -genkey -noout -out ec-secp256k1-priv-key.pem
        $privateKey = EC::createKey('secp256k1');

        // Export in PKCS#1 (traditional EC) format
        return $privateKey->toString('PKCS1');
    }
}
