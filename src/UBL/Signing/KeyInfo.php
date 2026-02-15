<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class KeyInfo extends BaseComponent
{
    private string $certificate;

    public function __construct(string $certificate)
    {
        $this->certificate = $certificate;

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
        return 'ds:KeyInfo';
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                elements: [
                    new BaseComponent(
                        value: $this->certificate,
                        name: 'ds:X509Certificate'
                    )
                ],
                attributes: [],
                value: '',
                name: 'ds:X509Data',
                index: null
            )
        ];
    }
}
