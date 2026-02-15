<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class PartyIdentification extends BaseComponent
{
    public const SCHEMA = [
        'id'        => 'string',
        'scheme_id' => 'string',
    ];

    private string $id;
    private string $schemeId;

    public function __construct(
        string $id,
        string $schemeId = 'CRN'
    ) {
        // https://zatca1.discourse.group/t/br-ksa-f-07-the-value-provided-in-other-buyer-id-bt-46-for-scheme-id-tin-appears-to-be-incorrect/8156/16
        // enforces format checks for other Seller IDs (BT-29) and other Buyer IDs (BT-46):
        //     CRN = exactly 10 digits.
        //     700 = 10 digits, starting with 7.
        //     TIN = 10 digits, starting with 3.
        //     NAT = 10 digits, starting with 1.
        //     IQA = 10 digits, starting with 2.
        //     All IDs must be alphanumeric only (no spaces, symbols).
        //     Buyer ID is mandatory if VAT number missing.

        $this->id        = $id;
        $this->schemeId  = $schemeId;

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
        return 'cac:PartyIdentification';
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                attributes: ['schemeID' => $this->schemeId],
                value: $this->id,
                name: 'cbc:ID'
            ),
        ];
    }
}
