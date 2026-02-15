<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class PartyLegalEntity extends BaseComponent
{
    private string $registrationName;

    public function __construct(
        string $registrationName
    ) {
        $this->registrationName = $registrationName;

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
        return 'cac:PartyLegalEntity';
    }

    public function elements(): array
    {
        return [
            BaseComponent::build(
                value: $this->registrationName,
                name: 'cbc:RegistrationName'
            ),
        ];
    }
}
