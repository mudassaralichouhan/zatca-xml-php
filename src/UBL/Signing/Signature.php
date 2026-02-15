<?php

namespace ZATCA\UBL\Signing;

use DateTimeImmutable;
use ZATCA\UBL\BaseComponent;

class Signature extends BaseComponent
{
    private string $invoiceDigest;
    private string $signedPropertiesHash;
    private string $signatureValue;
    private string $certificate;
    public string $signingTime;
    public string $certDigestValue;
    public string $certIssuerName;
    public string $certSerialNumber;

    public function __construct(
        string            $invoiceDigest,
        string            $signedPropertiesHash,
        string            $signatureValue,
        string            $certificate,
        string $signingTime,
        string            $certDigestValue,
        string            $certIssuerName,
        string            $certSerialNumber
    ) {
        $this->invoiceDigest = $invoiceDigest;
        $this->signedPropertiesHash = $signedPropertiesHash;
        $this->signatureValue = $signatureValue;
        $this->certificate = $certificate;
        $this->signingTime = $signingTime;
        $this->certDigestValue = $certDigestValue;
        $this->certIssuerName = $certIssuerName;
        $this->certSerialNumber = $certSerialNumber;

        parent::__construct(
            elements: $this->elements(),
            attributes: $this->attributes(),
            value: '',
            name: $this->name(),
            index: null
        );
    }

    public function attributes(): array
    {
        return [
            'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'Id' => 'signature',
        ];
    }

    public function name(): string
    {
        return 'ds:Signature';
    }

    public function elements(): array
    {
        return [
            new SignedInfo(
                invoiceDigest: $this->invoiceDigest,
                signedPropertiesHash: $this->signedPropertiesHash
            ),

            new BaseComponent(
                value: $this->signatureValue,
                name: 'ds:SignatureValue'
            ),

            new KeyInfo(
                certificate: $this->certificate
            ),

            new SigningObject(
                signingTime: $this->signingTime,
                certDigestValue: $this->certDigestValue,
                certIssuerName: $this->certIssuerName,
                certSerialNumber: $this->certSerialNumber
            ),
        ];
    }
}
