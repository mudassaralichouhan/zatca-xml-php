<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;
use ZATCA\UBL\Signing\UBLDocumentSignatures;

class UBLExtension extends BaseComponent
{
    private Signature $signature;

    public function __construct($signature)
    {
        parent::__construct();

        $this->signature = $signature;

        parent::__construct(
            elements: $this->elements(),
            attributes: [],
            value: '',     // no extra attributes on the container
            name: $this->name()
        );
    }

    public function name(): string
    {
        return 'ext:UBLExtension';
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                value: 'urn:oasis:names:specification:ubl:dsig:enveloped:xades',
                name: 'ext:ExtensionURI'
            ),
            new BaseComponent(
                elements: [
                    new UBLDocumentSignatures(signature: $this->signature),
                ],
                name: 'ext:ExtensionContent'
            ),
        ];
    }
}
