<?php

namespace ZATCA\UBL\Signing;

use ZATCA\UBL\BaseComponent;

class InvoiceSignedDataReference extends BaseComponent
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
             'Id'   => 'invoiceSignedData',
            // must reference the SignedProperties element itself:
             'URI'  => '',
            // 'Type' => 'http://uri.etsi.org/01903#SignedProperties',
        ];
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                elements: $this->transformElements(),
                name: 'ds:Transforms'
            ),
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

    private function nestedTransformElement(string $xpath): BaseComponent
    {
        return new BaseComponent(
            elements: [
                new BaseComponent(
                    value: $xpath,
                    name: 'ds:XPath'
                ),
            ],
            attributes: [
                'Algorithm' => 'http://www.w3.org/TR/1999/REC-xpath-19991116',
            ],
            name: 'ds:Transform'
        );
    }

    private function transformElements(): array
    {
        return [
            $this->nestedTransformElement("not(//ancestor-or-self::ext:UBLExtensions)"),
            $this->nestedTransformElement("not(//ancestor-or-self::cac:Signature)"),
            $this->nestedTransformElement("not(//ancestor-or-self::cac:AdditionalDocumentReference[cbc:ID='QR'])"),
            new BaseComponent(
                attributes: [
                    'Algorithm' => 'http://www.w3.org/2006/12/xml-c14n11',
                ],
                name: 'ds:Transform'
            ),
        ];
    }
}
