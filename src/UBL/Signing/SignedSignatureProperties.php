<?php

namespace ZATCA\UBL\Signing;

use DateTimeImmutable;
use ZATCA\UBL\BaseComponent;

class SignedSignatureProperties extends BaseComponent
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
        parent::__construct(
            elements: [],
            attributes: [],
            value: '',
            name: 'xades:SignedSignatureProperties',
            index: null
        );

        $this->signingTime      = $signingTime;
        $this->certDigestValue  = $certDigestValue;
        $this->certIssuerName   = $certIssuerName;
        $this->certSerialNumber = $certSerialNumber;
    }

    public function name(): string
    {
        return 'xades:SignedSignatureProperties';
    }

    /**
     * @return BaseComponent[]
     */
    public function elements(): array
    {
        return [
            new BaseComponent(
                value: $this->signingTime,
                name: 'xades:SigningTime'
            ),
            new BaseComponent(
                elements: [
                    new Cert(
                        certDigestValue:  $this->certDigestValue,
                        certIssuerName:   $this->certIssuerName,
                        certSerialNumber: $this->certSerialNumber
                    )
                ],
                name: 'xades:SigningCertificate'
            )
        ];
    }
}
