<?php

namespace ZATCA\Signing;

use InvalidArgumentException;
use phpseclib3\File\X509;
use phpseclib3\Math\BigInteger;

class Certificate
{
    public readonly string $certContentWithoutHeaders;
    public readonly string $hash;
    public readonly string $issuerName;
    public readonly string $serialNumber;
    public string $publicKey;
    public string $publicKeyWithoutHeaders;
    public readonly string $publicKeyBytes;
    public readonly string $signature;

    private X509 $x509;

    public static function readCertificate(string $cert): self
    {
        $pem = base64_decode($cert);

        if (!preg_match(
            '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s',
            $pem,
            $match
        )) {
            throw new InvalidArgumentException("No CERTIFICATE block found in “{$cert}”");
        }

        return new self($match[0]);
    }

    public function __construct(string $pemCertificate)
    {
        $this->x509 = new X509();
        if (!$this->x509->loadX509($pemCertificate)) {
            throw new InvalidArgumentException('Invalid X.509 certificate provided.');
        }

        $this->parseCertificate($pemCertificate);
    }

    private static function generateBase64Hash(string $data): string
    {
        return base64_encode(hash('sha256', $data));
    }

    private function parseCertificate(string $pemCertificate): void
    {
        $this->certContentWithoutHeaders = str_replace(
            ["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\r", "\n"],
            '',
            $pemCertificate
        );

        $this->hash = self::generateBase64Hash($this->certContentWithoutHeaders);

        $dn = $this->x509->getIssuerDN(X509::DN_OPENSSL);
        $cnParts = [];
        $dcParts = [];

        foreach ($dn as $attr => $vals) {
            $values = (array)$vals;
            if (strcasecmp($attr, 'CN') === 0 || $attr === '2.5.4.3') {
                // commonName
                foreach ($values as $val) {
                    $cnParts[] = "CN={$val}";
                }
            } elseif ($attr === '0.9.2342.19200300.100.1.25') {
                // domainComponent — phpseclib reverses these for you, so restore original order
                $values = array_reverse($values);
                foreach ($values as $val) {
                    $dcParts[] = "DC={$val}";
                }
            }
        }

        $this->issuerName = implode(', ', array_merge($cnParts, $dcParts));

        $curr = $this->x509->getCurrentCert();
        if (
            !isset($curr['tbsCertificate']['serialNumber']) ||
            !$curr['tbsCertificate']['serialNumber'] instanceof BigInteger
        ) {
            throw new InvalidArgumentException('Unable to extract serial number.');
        }
        $this->serialNumber = $curr['tbsCertificate']['serialNumber']->toString(10);

        $pemPub = $this->x509->getPublicKey()->toString('PKCS8');
        $this->publicKey = $pemPub;
        $this->publicKeyWithoutHeaders = str_replace(
            ["-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\r", "\n"],
            '',
            $pemPub
        );

        $this->publicKeyBytes = base64_decode($this->publicKeyWithoutHeaders, true);
        if ($this->publicKeyBytes === false) {
            throw new InvalidArgumentException('Failed to base64-decode public key');
        }

        if (empty($curr['signature'])) {
            throw new InvalidArgumentException('Certificate contains no signature.');
        }

        $this->signature = substr($curr['signature'], 1);
    }
}
