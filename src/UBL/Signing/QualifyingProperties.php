<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;
use DateTimeImmutable;

class QualifyingProperties extends BaseComponent
{
    private readonly string $signingTime;
    private readonly string $certDigestValue;
    private readonly string $certIssuerName;
    private readonly string $certSerialNumber;

    public function __construct(
        string $signingTime,
        string $certDigestValue,
        string $certIssuerName,
        string $certSerialNumber
    ) {
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

    public function name(): string
    {
        return 'xades:QualifyingProperties';
    }

    public function attributes(): array
    {
        return [
            'Target' => 'signature',
            'xmlns:xades' => 'http://uri.etsi.org/01903/v1.3.2#',
        ];
    }

    public function elements(): array
    {
        return [
            new SignedProperties(
                signingTime: $this->signingTime,
                certDigestValue: $this->certDigestValue,
                certIssuerName: $this->certIssuerName,
                certSerialNumber: $this->certSerialNumber
            ),
        ];
    }
}
