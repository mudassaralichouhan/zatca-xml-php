<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class Cert extends BaseComponent
{
    private string $certDigestValue;
    private string $certIssuerName;
    private string $certSerialNumber;

    public function __construct(
        string $certDigestValue,
        string $certIssuerName,
        string $certSerialNumber
    ) {
        $this->certDigestValue  = $certDigestValue;
        $this->certIssuerName   = $certIssuerName;
        $this->certSerialNumber = $certSerialNumber;

        parent::__construct(
            elements: $this->elements(),
            attributes: [],
            value: '',
            name: 'xades:Cert',
            index: null
        );
    }

    public function name(): string
    {
        return 'xades:Cert';
    }

    /**
     * @return BaseComponent[]
     */
    public function elements(): array
    {
        return [
            new BaseComponent(
                elements: [
                    new BaseComponent(
                        attributes: ['Algorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256'],
                        name: 'ds:DigestMethod'
                    ),
                    new BaseComponent(
                        value: $this->certDigestValue,
                        name: 'ds:DigestValue'
                    )
                ],
                name: 'xades:CertDigest'
            ),
            new BaseComponent(
                elements: [
                    new BaseComponent(
                        value: $this->certIssuerName,
                        name: 'ds:X509IssuerName'
                    ),
                    new BaseComponent(
                        value: $this->certSerialNumber,
                        name: 'ds:X509SerialNumber'
                    )
                ],
                name: 'xades:IssuerSerial'
            )
        ];
    }
}
