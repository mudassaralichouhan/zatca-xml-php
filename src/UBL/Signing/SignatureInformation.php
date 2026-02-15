<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class SignatureInformation extends BaseComponent
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
            index: null
        );
    }

    public function name(): string
    {
        return 'sac:SignatureInformation';
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                value: 'urn:oasis:names:specification:ubl:signature:1',
                name: 'cbc:ID'
            ),
            new BaseComponent(
                value: 'urn:oasis:names:specification:ubl:signature:Invoice',
                name: 'sbc:ReferencedSignatureID'
            ),
            $this->signature,
        ];
    }
}
