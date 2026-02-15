<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class SignaturePropertiesReference extends BaseComponent
{
    private string $digestValue;

    public function __construct(string $digestValue)
    {
        $this->digestValue = $digestValue;

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
        return 'ds:Reference';
    }

    public function attributes(): array
    {
        return [
            'Type' => 'http://www.w3.org/2000/09/xmldsig#SignatureProperties',
            // 'Id'  => 'invoiceSignedData',
            'URI'  => '#xadesSignedProperties',
        ];
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                attributes: [
                    'Algorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                ],
                name: 'ds:DigestMethod'
            ),
            new BaseComponent(
                value: $this->digestValue,
                name: 'ds:DigestValue'
            ),
        ];
    }
}
