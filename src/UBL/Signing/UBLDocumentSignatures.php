<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class UBLDocumentSignatures extends BaseComponent
{
    private Signature $signature;

    public function __construct(Signature $signature)
    {
        $this->signature = $signature;

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
        return 'sig:UBLDocumentSignatures';
    }

    public function attributes(): array
    {
        return [
            'xmlns:sig' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2',
            'xmlns:sac' => 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2',
            'xmlns:sbc' => 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2',
        ];
    }

    public function elements(): array
    {
        return [
            new SignatureInformation(signature: $this->signature),
        ];
    }
}
