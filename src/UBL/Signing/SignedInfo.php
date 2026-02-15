<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class SignedInfo extends BaseComponent
{
    private string $invoiceDigest;
    private string $signedPropertiesHash;

    public function __construct(
        string $invoiceDigest,
        string $signedPropertiesHash
    ) {
        $this->invoiceDigest         = $invoiceDigest;
        $this->signedPropertiesHash  = $signedPropertiesHash;

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
        return 'ds:SignedInfo';
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                attributes: ['Algorithm' => 'http://www.w3.org/2006/12/xml-c14n11'],
                name: 'ds:CanonicalizationMethod'
            ),
            new BaseComponent(
                attributes: ['Algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256'],
                name: 'ds:SignatureMethod'
            ),
            new InvoiceSignedDataReference(
                digestValue: $this->invoiceDigest
            ),
            new SignaturePropertiesReference(
                digestValue: $this->signedPropertiesHash
            ),
        ];
    }
}
