<?php

namespace App\Services;

use Exception;
use App\Config\Config;

class CryptoService
{
    private static $encryption_method = 'AES-256-CBC';

    private static function getSecretKey(): string
    {
        return Config::CRYPTO_SECRET_KEY();
    }

    // Function to encrypt data
    public static function encrypt($data)
    {
        $key = self::getSecretKey();
        // Generate an initialization vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$encryption_method));
        // Encrypt the data
        $encrypted = openssl_encrypt($data, self::$encryption_method, $key, 0, $iv);

        if ($encrypted === false) {
            throw new Exception('Encryption failed!');
        }

        // Return the encrypted data along with the IV for decryption, encoded in base64
        return base64_encode($encrypted . '::' . $iv);
    }

    public static function decrypt($data)
    {
        $key = self::getSecretKey();
        $decoded = base64_decode($data);

        if ($decoded === false) {
            throw new Exception('Invalid base64 data!');
        }

        // Split the encrypted data and IV from the base64-encoded string
        $parts = explode('::', $decoded, 2);

        if (count($parts) !== 2) {
            throw new Exception('Invalid encrypted data format!');
        }

        list($encrypted_data, $iv) = $parts;

        // Validate and fix IV length
        $expected_iv_length = openssl_cipher_iv_length(self::$encryption_method);
        if (strlen($iv) !== $expected_iv_length) {
            if (strlen($iv) < $expected_iv_length) {
                // Pad IV with null bytes if it's too short
                $iv = str_pad($iv, $expected_iv_length, "\0");
            } else {
                throw new Exception("Invalid IV length. Expected {$expected_iv_length} bytes, got " . strlen($iv) . " bytes");
            }
        }

        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted_data, self::$encryption_method, $key, 0, $iv);

        // Check if decryption was successful
        if ($decrypted === false) {
            throw new Exception('Decryption failed!');
        }

        // Return the decrypted data
        return $decrypted;
    }
}
