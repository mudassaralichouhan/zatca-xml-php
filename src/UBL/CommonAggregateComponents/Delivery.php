<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class Delivery extends BaseComponent
{
    private string  $actualDeliveryDate;
    private ?string $latestDeliveryDate;

    public function __construct(
        string  $actualDeliveryDate,
        ?string $latestDeliveryDate = null
    ) {
        $this->actualDeliveryDate = $actualDeliveryDate;
        $this->latestDeliveryDate = $latestDeliveryDate;

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
        return 'cac:Delivery';
    }

    private function latestDeliveryDateElement(): ?BaseComponent
    {
        if ($this->latestDeliveryDate === null) {
            return null;
        }

        return new BaseComponent(
            value: $this->latestDeliveryDate,
            name: 'cbc:LatestDeliveryDate'
        );
    }

    public function elements(): array
    {
        return array_values(array_filter([
            new BaseComponent(
                value: $this->actualDeliveryDate,
                name: 'cbc:ActualDeliveryDate'
            ),
            $this->latestDeliveryDateElement(),
        ]));
    }
}
