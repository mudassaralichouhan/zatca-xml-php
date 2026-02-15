<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class Price extends BaseComponent
{
    private string          $priceAmount;
    private ?BaseComponent  $allowanceCharge;
    private string          $currencyId;

    public function __construct(
        string         $priceAmount,
        ?BaseComponent $allowanceCharge = null,
        string         $currencyId      = 'SAR'
    ) {
        $this->priceAmount     = $priceAmount;
        $this->allowanceCharge = $allowanceCharge;
        $this->currencyId      = $currencyId;

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
        return 'cac:Price';
    }

    public function elements(): array
    {
        return array_filter([
            new BaseComponent(
                attributes: ['currencyID' => $this->currencyId],
                value: $this->priceAmount,
                name: 'cbc:PriceAmount'
            ),
            $this->allowanceCharge,
        ]);
    }
}
