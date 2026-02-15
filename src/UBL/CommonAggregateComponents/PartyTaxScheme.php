<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class PartyTaxScheme extends BaseComponent
{
    private ?string $companyId;
    private string  $taxSchemeId;

    public function __construct(
        ?string $companyId     = null,
        string  $taxSchemeId   = 'VAT'
    ) {
        $this->companyId   = $companyId;
        $this->taxSchemeId = $taxSchemeId;

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
        return 'cac:PartyTaxScheme';
    }

    private function companyIdElement(): ?BaseComponent
    {
        if ($this->companyId === null || $this->companyId === '') {
            return null;
        }

        return new BaseComponent(
            elements: [],
            attributes: [],
            value: $this->companyId,
            name: 'cbc:CompanyID',
            index: null
        );
    }

    /**
     * @return BaseComponent[]|null[]
     */
    public function elements(): array
    {
        return [
            $this->companyIdElement(),
            new BaseComponent(
                elements: [
                    new BaseComponent(
                        elements: [],
                        attributes: [],
                        value: $this->taxSchemeId,
                        name: 'cbc:ID',
                        index: null
                    )
                ],
                attributes: [],
                value: '',
                name: 'cac:TaxScheme',
                index: null
            )
        ];
    }
}
