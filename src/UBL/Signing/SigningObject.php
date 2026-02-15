<?php

namespace ZATCA\UBL\Signing;

use DateTimeImmutable;
use ZATCA\UBL\BaseComponent;

class SigningObject extends BaseComponent
{
    private string $signingTime;
    private string $certDigestValue;
    private string $certIssuerName;
    private string $certSerialNumber;

    public function __construct(
        string $signingTime,
        string $certDigestValue,
        string $certIssuerName,
        string $certSerialNumber
    ) {
        $this->signingTime     = $signingTime;
        $this->certDigestValue = $certDigestValue;
        $this->certIssuerName  = $certIssuerName;
        $this->certSerialNumber = $certSerialNumber;

        parent::__construct(
            elements: $this->elements(),
            attributes: [],
            value: '',
            name: $this->name(),
            index: null
        );
    }

    public function name(): string
    {
        return 'ds:Object';
    }

    /**
     * @return BaseComponent[]
     */
    public function elements(): array
    {
        return [
            new QualifyingProperties(
                signingTime:       $this->signingTime,
                certDigestValue:   $this->certDigestValue,
                certIssuerName:    $this->certIssuerName,
                certSerialNumber:  $this->certSerialNumber
            ),
        ];
    }
}
