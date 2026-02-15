<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class LegalMonetaryTotal extends BaseComponent
{
    private string  $lineExtensionAmount;
    private string  $taxExclusiveAmount;
    private string  $taxInclusiveAmount;
    private string  $allowanceTotalAmount;
    private ?string $prepaidAmount;
    public readonly ?string $payableAmount;
    private string  $currencyId;

    public function __construct(
        string  $lineExtensionAmount,
        string  $taxExclusiveAmount,
        string  $taxInclusiveAmount,
        string  $allowanceTotalAmount,
        ?string $prepaidAmount        = null,
        ?string $payableAmount        = null,
        string  $currencyId           = 'SAR'
    ) {
        $this->lineExtensionAmount  = $lineExtensionAmount;
        $this->taxExclusiveAmount   = $taxExclusiveAmount;
        $this->taxInclusiveAmount   = $taxInclusiveAmount;
        $this->allowanceTotalAmount = $allowanceTotalAmount;
        $this->prepaidAmount        = $prepaidAmount;
        $this->payableAmount        = $payableAmount;
        $this->currencyId           = $currencyId;

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
        return 'cac:LegalMonetaryTotal';
    }

    private function prepaidAmountElement(): ?BaseComponent
    {
        if ($this->prepaidAmount === null || $this->prepaidAmount === '') {
            return null;
        }

        return new BaseComponent(
            attributes: ['currencyID' => $this->currencyId],
            value: $this->prepaidAmount,
            name: 'cbc:PrepaidAmount'
        );
    }

    private function payableAmountElement(): ?BaseComponent
    {
        if ($this->payableAmount === null || $this->payableAmount === '') {
            return null;
        }

        return new BaseComponent(
            attributes: ['currencyID' => $this->currencyId],
            value: $this->payableAmount,
            name: 'cbc:PayableAmount'
        );
    }

    public function elements(): array
    {
        $elements = [
            new BaseComponent(
                attributes: ['currencyID' => $this->currencyId],
                value: $this->lineExtensionAmount,
                name: 'cbc:LineExtensionAmount'
            ),
            new BaseComponent(
                attributes: ['currencyID' => $this->currencyId],
                value: $this->taxExclusiveAmount,
                name: 'cbc:TaxExclusiveAmount'
            ),
            new BaseComponent(
                attributes: ['currencyID' => $this->currencyId],
                value: $this->taxInclusiveAmount,
                name: 'cbc:TaxInclusiveAmount'
            ),
            new BaseComponent(
                attributes: ['currencyID' => $this->currencyId],
                value: $this->allowanceTotalAmount,
                name: 'cbc:AllowanceTotalAmount'
            ),
            $this->prepaidAmountElement(),
            $this->payableAmountElement(),
        ];

        // remove any null entries
        return array_values(array_filter($elements));
    }
}
