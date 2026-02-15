<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class PostalAddress extends BaseComponent
{
    private ?string $streetName;
    private ?string $additionalStreetName;
    private ?string $buildingNumber;
    private ?string $plotIdentification;
    private ?string  $citySubdivisionName;
    private ?string $cityName;
    private ?string $postalZone;
    private ?string $countrySubentity;
    private string  $countryIdentificationCode;

    public function __construct(
        ?string $streetName,
        ?string $buildingNumber,
        ?string $plotIdentification,
        ?string $citySubdivisionName,
        ?string $cityName,
        ?string $postalZone,
        ?string $countrySubentity,
        ?string $additionalStreetName = null,
        string  $countryIdentificationCode = 'SA'
    ) {
        $this->streetName               = $streetName;
        $this->additionalStreetName     = $additionalStreetName;
        $this->buildingNumber           = $buildingNumber;
        $this->plotIdentification       = $plotIdentification;
        $this->citySubdivisionName      = $citySubdivisionName;
        $this->cityName                 = $cityName;
        $this->postalZone               = $postalZone;
        $this->countrySubentity         = $countrySubentity;
        $this->countryIdentificationCode = $countryIdentificationCode;

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
        return 'cac:PostalAddress';
    }

    public function elements(): array
    {
        $elements = [];

        if ($this->streetName !== null) {
            $elements[] = BaseComponent::build(
                value: $this->streetName,
                name: 'cbc:StreetName'
            );
        }

        if ($this->additionalStreetName !== null) {
            $elements[] = BaseComponent::build(
                value: $this->additionalStreetName,
                name: 'cbc:AdditionalStreetName'
            );
        }

        if ($this->buildingNumber !== null) {
            $elements[] = BaseComponent::build(
                value: $this->buildingNumber,
                name: 'cbc:BuildingNumber'
            );
        }

        if ($this->plotIdentification !== null) {
            $elements[] = BaseComponent::build(
                value: $this->plotIdentification,
                name: 'cbc:PlotIdentification'
            );
        }

        if ($this->citySubdivisionName !== null) {
            $elements[] = BaseComponent::build(
                value: $this->citySubdivisionName,
                name: 'cbc:CitySubdivisionName'
            );
        }

        if ($this->cityName !== null) {
            $elements[] = BaseComponent::build(
                value: $this->cityName,
                name: 'cbc:CityName'
            );
        }

        if ($this->postalZone !== null) {
            $elements[] = BaseComponent::build(
                value: $this->postalZone,
                name: 'cbc:PostalZone'
            );
        }

        if ($this->countrySubentity !== null) {
            $elements[] = BaseComponent::build(
                value: $this->countrySubentity,
                name: 'cbc:CountrySubentity'
            );
        }

        // always include Country block
        $elements[] = BaseComponent::build(
            elements: [
                BaseComponent::build(
                    value: $this->countryIdentificationCode,
                    name: 'cbc:IdentificationCode'
                )
            ],
            name: 'cac:Country'
        );

        // drop any null entries
        return array_values(array_filter($elements));
    }
}
