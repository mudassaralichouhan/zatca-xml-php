<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class AllowanceCharge extends BaseComponent
{
    public ?int $index;
    private string $chargeIndicator;
    private string $allowanceChargeReason;
    private int|float|string $amount;
    private string $currencyID;
    private bool $addID;
    private bool $addTaxCategory;
    private array $taxCategories;

    public function __construct(
        bool|string      $chargeIndicator,
        string           $allowanceChargeReason,
        int|float|string $amount,
        string           $currencyID = 'SAR',
        ?TaxCategory     $taxCategory = null,
        bool             $addTaxCategory = true,
        bool             $addID = true,
        array            $taxCategories = [],
        ?int              $index = null,
    ) {
        // satisfy the inherited index property
        $this->index = $index;

        // normalize the boolean/string
        $this->chargeIndicator = is_bool($chargeIndicator)
            ? ($chargeIndicator ? 'true' : 'false')
            : (string)$chargeIndicator;
        $this->allowanceChargeReason = $allowanceChargeReason;
        $this->amount = $amount;
        $this->currencyID = $currencyID;
        $this->addID = $addID;
        $this->addTaxCategory = $addTaxCategory;

        // if requested, inject a default TaxCategory
        if ($this->addTaxCategory && $taxCategory === null) {
            $taxCategory = new TaxCategory();
        }

        // decide final taxCategories array
        $this->taxCategories = (
            $taxCategory !== null && empty($taxCategories)
            ? [$taxCategory]
            : $taxCategories
        );

        parent::__construct(
            elements: $this->elements(),
            attributes: [],
            value: '',
            name: 'cac:AllowanceCharge',
            index: $this->index
        );
    }

    private function idElement(): ?BaseComponent
    {
        if ($this->addID && $this->index !== null) {
            return new BaseComponent(
                value: (string)$this->index,
                name: 'cbc:ID'
            );
        }
        return null;
    }

    public function elements(): array
    {
        $elements = [];

        if ($id = $this->idElement()) {
            $elements[] = $id;
        }

        $elements[] = new BaseComponent(
            value: $this->chargeIndicator,
            name: 'cbc:ChargeIndicator'
        );

        $elements[] = new BaseComponent(
            value: $this->allowanceChargeReason,
            name: 'cbc:AllowanceChargeReason'
        );

        $elements[] = new BaseComponent(
            attributes: ['currencyID' => $this->currencyID],
            value: (string)$this->amount,
            name: 'cbc:Amount'
        );

        // append each TaxCategory
        foreach ($this->taxCategories as $tc) {
            $elements[] = $tc;
        }

        return $elements;
    }
}
