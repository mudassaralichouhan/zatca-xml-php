<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class UBLExtensions extends BaseComponent
{
    private Signature $signature;

    public function __construct(Signature $signature)
    {
        $this->signature = $signature;

        parent::__construct(
            elements: $this->elements(),
            attributes: [],
            value: '',
            name: $this->name(),
        );
    }

    public function name(): string
    {
        return 'ext:UBLExtensions';
    }

    public function elements(): array
    {
        return [
            new UBLExtension(signature: $this->signature),
        ];
    }
}
