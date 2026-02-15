<?php

namespace ZATCA\UBL\CommonAggregateComponents;

use ZATCA\UBL\BaseComponent;

class TaxCategory extends BaseComponent
{
    private string  $taxPercent;
    private string  $id;
    private string  $schemeAgencyId;
    private string  $schemeId;
    private string  $taxSchemeId;
    private string  $taxSchemeSchemeId;
    private ?string $taxExemptionReasonCode;
    private ?string $taxExemptionReason;

    public function __construct(
        string  $taxPercent               = '15.00',
        string  $id                       = 'S',
        string  $schemeAgencyId           = '6',
        string  $schemeId                 = 'UN/ECE 5305',
        string  $taxSchemeId              = 'VAT',
        string  $taxSchemeSchemeId        = 'UN/ECE 5153',
        ?string $taxExemptionReasonCode   = null,
        ?string $taxExemptionReason       = null
    ) {
        $this->taxPercent             = $taxPercent;
        $this->id                     = $id;
        $this->schemeAgencyId         = $schemeAgencyId;
        $this->schemeId               = $schemeId;
        $this->taxSchemeId            = $taxSchemeId;
        $this->taxSchemeSchemeId      = $taxSchemeSchemeId;
        $this->taxExemptionReasonCode = $taxExemptionReasonCode;
        $this->taxExemptionReason     = $taxExemptionReason;

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
        return 'cac:TaxCategory';
    }

    private function taxExemptionReasonCodeElement(): ?BaseComponent
    {
        if ($this->taxExemptionReasonCode !== null && $this->taxExemptionReasonCode !== '') {
            return new BaseComponent(
                elements: [],
                attributes: [],
                value: $this->taxExemptionReasonCode,
                name: 'cbc:TaxExemptionReasonCode',
                index: null
            );
        }
        if ($this->taxExemptionReason !== null && $this->taxExemptionReason !== '') {
            return new BaseComponent(
                elements: [],
                attributes: [],
                value: $this->taxExemptionReason,
                name: 'cbc:TaxExemptionReason',
                index: null
            );
        }
        return null;
    }

    public function elements(): array
    {
        $elements = [
            new BaseComponent(
                elements: [],
                attributes: [
                    'schemeAgencyID' => $this->schemeAgencyId,
                    'schemeID'       => $this->schemeId,
                ],
                value: $this->id,
                name: 'cbc:ID',
                index: null
            ),
            $this->taxExemptionReasonCodeElement(),
            new BaseComponent(
                elements: [],
                attributes: [],
                value: $this->taxPercent,
                name: 'cbc:Percent',
                index: null
            ),
            new BaseComponent(
                elements: [
                    new BaseComponent(
                        elements: [],
                        attributes: [
                            'schemeAgencyID' => $this->schemeAgencyId,
                            'schemeID'       => $this->taxSchemeSchemeId,
                        ],
                        value: $this->taxSchemeId,
                        name: 'cbc:ID',
                        index: null
                    ),
                ],
                attributes: [],
                value: '',
                name: 'cac:TaxScheme',
                index: null
            ),
        ];

        // filter out any null entries
        return array_values(array_filter($elements));
    }
}
