<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class InvoiceLine extends BaseComponent
{
    public readonly int $idIdx;
    private int|float|string $invoicedQuantity;
    private string $invoicedQuantityUnitCode;
    private int|float|string $lineExtensionAmount;
    private BaseComponent $taxTotal;
    private BaseComponent $item;
    private BaseComponent $price;
    private ?BaseComponent $allowanceCharge;
    private string $currencyID;

    public function __construct(
        int|float|string $invoicedQuantity,
        string           $invoicedQuantityUnitCode,
        int|float|string $lineExtensionAmount,
        BaseComponent    $taxTotal,
        BaseComponent    $item,
        BaseComponent    $price,
        $idIdx = 0,
        ?BaseComponent   $allowanceCharge = null,
        string           $currencyID = 'SAR'
    ) {
        $this->idIdx = $idIdx;
        $this->invoicedQuantity = $invoicedQuantity;
        $this->invoicedQuantityUnitCode = $invoicedQuantityUnitCode;
        $this->lineExtensionAmount = $lineExtensionAmount;
        $this->taxTotal = $taxTotal;
        $this->item = $item;
        $this->price = $price;
        $this->allowanceCharge = $allowanceCharge;
        $this->currencyID = $currencyID;

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
        return 'cac:InvoiceLine';
    }

    public function elements(): array
    {
        return array_filter([
            new BaseComponent(
                value: $this->idIdx,
                name: 'cbc:ID'
            ),
            new BaseComponent(
                attributes: ['unitCode' => $this->invoicedQuantityUnitCode],
                value: (string)$this->invoicedQuantity,
                name: 'cbc:InvoicedQuantity'
            ),
            new BaseComponent(
                attributes: ['currencyID' => $this->currencyID],
                value: (string)$this->lineExtensionAmount,
                name: 'cbc:LineExtensionAmount'
            ),
            $this->taxTotal,
            $this->item,
            $this->price,
            $this->allowanceCharge,
        ]);
    }
}
