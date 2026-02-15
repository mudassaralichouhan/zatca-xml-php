<?php

namespace ZATCA;

class Hashing
{
    private function __construct()
    {
    }

    public static function generateHashes(string $content): array
    {
        // Raw binary digest
        $rawDigest = hash('sha256', $content, true);

        // Hexadecimal digest
        $hexDigest = hash('sha256', $content, false);

        // Base64 of raw binary digest
        $base64Raw = base64_encode($rawDigest);

        // Base64 of hexadecimal digest
        $base64Hex = base64_encode($hexDigest);

        return [
            'hash' => $rawDigest,
            'hexdigest' => $hexDigest,
            'base64' => $base64Raw,
            'hexdigest_base64' => $base64Hex,
        ];
    }

    public static function uuidv4(): string
    {
        $data = random_bytes(16);
        // set version to 0100
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // set bits 6-7 to 10
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        // output the 36-char UUID
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
