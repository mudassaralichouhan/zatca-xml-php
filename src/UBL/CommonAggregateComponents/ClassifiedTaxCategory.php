<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class ClassifiedTaxCategory extends BaseComponent
{
    private string $id;
    private string $percent;
    private string $taxSchemeId;

    public function __construct(
        string $id           = 'S',
        string $percent      = '15.00',
        string $taxSchemeId  = 'VAT'
    ) {
        $this->id           = $id;
        $this->percent      = $percent;
        $this->taxSchemeId  = $taxSchemeId;

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
        return 'cac:ClassifiedTaxCategory';
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                value: $this->id,
                name: 'cbc:ID'
            ),
            new BaseComponent(
                value: $this->percent,
                name: 'cbc:Percent'
            ),
            new BaseComponent(
                elements: [
                    new BaseComponent(
                        value: $this->taxSchemeId,
                        name: 'cbc:ID'
                    )
                ],
                name: 'cac:TaxScheme'
            ),
        ];
    }
}
