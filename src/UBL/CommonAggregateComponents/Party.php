<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class Party extends BaseComponent
{
    private ?PartyIdentification $partyIdentification;
    private PostalAddress       $postalAddress;
    private PartyTaxScheme      $partyTaxScheme;
    private ?PartyLegalEntity    $partyLegalEntity;

    public function __construct(
        PostalAddress       $postalAddress,
        PartyTaxScheme      $partyTaxScheme,
        ?PartyLegalEntity    $partyLegalEntity,
        ?PartyIdentification $partyIdentification,
    ) {
        $this->partyIdentification = $partyIdentification;
        $this->postalAddress       = $postalAddress;
        $this->partyTaxScheme      = $partyTaxScheme;
        $this->partyLegalEntity    = $partyLegalEntity;

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
        return 'cac:Party';
    }

    public function elements(): array
    {
        return array_values(array_filter([
            $this->partyIdentification,
            $this->postalAddress,
            $this->partyTaxScheme,
            $this->partyLegalEntity,
        ]));
    }
}
