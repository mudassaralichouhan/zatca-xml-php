<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class Item extends BaseComponent
{
    private string $itemName;
    private ClassifiedTaxCategory $classifiedTaxCategory;

    public function __construct(
        string $name,
        ?ClassifiedTaxCategory $classifiedTaxCategory = null
    ) {
        $this->itemName               = $name;
        $this->classifiedTaxCategory  = $classifiedTaxCategory
                                        ?? new ClassifiedTaxCategory();

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
        return 'cac:Item';
    }

    public function elements(): array
    {
        return [
            new BaseComponent(
                value: $this->itemName,
                name: 'cbc:Name'
            ),
            $this->classifiedTaxCategory,
        ];
    }
}
