<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class TaxTotal extends BaseComponent
{
    private string             $taxAmount;
    private ?string            $taxSubtotalAmount;
    private ?string            $taxableAmount;
    private ?string            $roundingAmount;
    private TaxCategory        $taxCategory;
    private string             $currencyId;

    public function __construct(
        string    $taxAmount,
        ?string   $taxSubtotalAmount = null,
        ?string   $taxableAmount     = null,
        ?string   $roundingAmount    = null,
        ?TaxCategory $taxCategory    = null,
        string    $currencyId        = 'SAR'
    ) {
        $this->taxAmount         = $taxAmount;
        $this->taxSubtotalAmount = $taxSubtotalAmount;
        $this->taxableAmount     = $taxableAmount;
        $this->roundingAmount    = $roundingAmount;
        $this->taxCategory       = $taxCategory ?? new TaxCategory();
        $this->currencyId        = $currencyId;

        parent::__construct(
            elements: $this->elements(),
            attributes: [],
            value: '',
            name: 'cac:TaxTotal',
            index: null
        );
    }

    public function name(): string
    {
        return 'cac:TaxTotal';
    }

    public function elements(): array
    {
        // build elements array
        $elements = [
            new BaseComponent(
                attributes: ['currencyID' => $this->currencyId],
                value: $this->taxAmount,
                name: 'cbc:TaxAmount'
            ),
            $this->roundingAmountElement(),
            $this->taxSubtotalElement(),
        ];

        // filter out any null entries
        return array_values(array_filter($elements));
    }

    private function taxSubtotalElement(): ?BaseComponent
    {
        if ($this->taxableAmount !== null
            && $this->taxSubtotalAmount !== null
            && $this->taxCategory !== null
        ) {
            return new BaseComponent(
                elements: [
                    new BaseComponent(
                        attributes: ['currencyID' => $this->currencyId],
                        value: $this->taxableAmount,
                        name: 'cbc:TaxableAmount'
                    ),
                    new BaseComponent(
                        attributes: ['currencyID' => $this->currencyId],
                        value: $this->taxSubtotalAmount,
                        name: 'cbc:TaxAmount'
                    ),
                    $this->taxCategory,
                ],
                name: 'cac:TaxSubtotal'
            );
        }

        return null;
    }

    private function roundingAmountElement(): ?BaseComponent
    {
        if ($this->roundingAmount !== null) {
            return new BaseComponent(
                attributes: ['currencyID' => $this->currencyId],
                value: $this->roundingAmount,
                name: 'cbc:RoundingAmount'
            );
        }

        return null;
    }
}
