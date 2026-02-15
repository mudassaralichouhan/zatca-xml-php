<?php

namespace App\Services;

use ZATCA\UBL\CommonAggregateComponents\{Delivery,
    Party,
    PartyIdentification,
    PostalAddress,
    PartyTaxScheme,
    PartyLegalEntity,
    AllowanceCharge,
    TaxCategory,
    TaxTotal,
    LegalMonetaryTotal,
    InvoiceLine,
    Item,
    Price
};
use ZATCA\Hashing;
use ZATCA\Tags;
use ZATCA\UBL\InvoiceSubtypeBuilder;
use ZATCA\UBL\Invoice;

class InvoiceBuilderService
{
    public static function build(
        object  $csrOptions,
        string  $privateKeyBase64,
        string  $CertificateBase4,
        object  $payload,
        bool    $simplified,
        string  $invoiceType,
        int     $invoiceCounterValue,
        ?string $previousInvoiceHash,
    ): array {
        $prefix = $simplified ? 'SIM' : 'STD';

        $invoiceId = $payload->invoice_id ?? null;
        if (!$invoiceId) {
            $invoiceId = sprintf(
                '%s_%02d%02d%02d-%09d',
                $prefix,
                date('y'),
                date('m'),
                date('d'),
                mt_rand(10, 9999)
            );
        }
        $invoiceUuid = $payload->uuid ?? Hashing::uuidv4();
        $note = $payload->note;
        $noteLanguageId = $payload->note_language_id;
        $issueDate = $payload->issue_date;
        $issueTime = $payload->issue_time;

        $invoiceSubType = InvoiceSubtypeBuilder::build(
            simplified: $simplified, // true = simplified, false = standard
            thirdParty: $payload->invoice_sub_type->third_party,
            nominal: $payload->invoice_sub_type->nominal,
            exports: $payload->invoice_sub_type->exports,
            summary: $payload->invoice_sub_type->summary,
            selfBilled: $payload->invoice_sub_type->self_billed,
        );

        $paymentMeansCode = Invoice::PAYMENT_MEANS[$payload->payment_means_code]; // 'cash', 'credit', 'bank_account', 'bank_card'
        $invoiceType = Invoice::TYPES[$invoiceType]; // 'invoice', 'debit', 'credit'

        $currencyCode = $payload->currency_code;
        $vatNo = $csrOptions->organization_identifier; // vat register number

        $accountingSupplierParty = self::accountingSupplierParty(
            country: $csrOptions->country,
            vat: $vatNo,
            organizationName: $csrOptions->organization_name,
            accountingSupplierParty: $payload->accounting_supplier_party,
        );

        $accountingCustomerParty = self::accountingCustomerParty(
            accountingSupplierParty: $payload->accounting_customer_party,
        );

        if ($simplified && $invoiceType === '388') {
            $delivery = null;
        } else {
            $delivery = new Delivery(
                actualDeliveryDate: $payload->delivery->actual_delivery_date ?? null,
                latestDeliveryDate: $payload->delivery->latest_delivery_date ?? null,
            );
        }

        $allowanceCharges = self::allowanceCharges(allowanceCharges: $payload->allowance_charges);

        $taxTotals = self::taxTotals(taxTotals: $payload->tax_totals);

        $legalMonetaryTotal = self::legalMonetaryTotal(data: $payload->legal_monetary_total);

        $invoiceLines = self::invoiceLines(lines: $payload->invoice_lines);

        if (!empty($payload->billing_reference) && $invoiceType != '388') {
            $billingReference = "Invoice Number: {$payload->billing_reference->invoice_id}; Invoice Issue Date: {$payload->billing_reference->issue_date}";
        }

        $invoice = new Invoice(
            id: $invoiceId,
            uuid: $invoiceUuid,
            issueDate: $issueDate,
            issueTime: $issueTime,
            subtype: $invoiceSubType,
            type: $invoiceType,
            invoiceCounterValue: (string)$invoiceCounterValue,
            accountingSupplierParty: $accountingSupplierParty,
            accountingCustomerParty: $accountingCustomerParty,
            paymentMeansCode: $paymentMeansCode,
            legalMonetaryTotal: $legalMonetaryTotal,
            allowanceCharges: $allowanceCharges,
            taxTotals: $taxTotals,
            invoiceLines: $invoiceLines,
            currencyCode: $currencyCode,
            note: $note,
            noteLanguageId: $noteLanguageId,
            delivery: $delivery,
            previousInvoiceHash: $previousInvoiceHash,
            addIdsToAllowanceCharges: false,
            instructionNote: $payload->instruction_note ?? null,
            billingReference: $billingReference ?? null,
        );

        $invoiceHash = self::sign(
            invoice: $invoice,
            privateKeyBase64: $privateKeyBase64,
            csrBase64: $CertificateBase4,
        );

        $tags = self::qr(
            invoice: $invoice,
            payableAmount: $legalMonetaryTotal->payableAmount,
            taxAmount: $payload->tax_totals[0]->tax_amount,
            vat: $vatNo,
            organizationName: $csrOptions->organization_name,
            invoiceHash: $invoiceHash,
        );

        $invoice->qrCode = $tags->toBase64();

        return [$invoice->uuid, $invoiceHash, $invoice->toBase64(), $invoiceId, $invoiceUuid, $invoice->qrCode, $invoice->unsignedXmlBase64];
    }

    private static function sign(Invoice $invoice, string $privateKeyBase64, string $csrBase64): string
    {
        $invoiceTimestamp = "{$invoice->issueDate}T{$invoice->issueTime}";

        $invoice->sign(
            privateKey: $privateKeyBase64,
            certificate: $csrBase64,
            signingTime: $invoiceTimestamp,
        );

        return $invoice->generateHash();
    }

    private static function qr(Invoice $invoice, string $payableAmount, string $taxAmount, string $vat, string $organizationName, string $invoiceHash): Tags
    {
        $invoiceTimestamp = "{$invoice->issueDate}T{$invoice->issueTime}";

        return new Tags([
            'seller_name' => $organizationName,
            'vat_registration_number' => $vat,
            'timestamp' => $invoiceTimestamp,
            'invoice_total' => $payableAmount,
            'vat_total' => $taxAmount,
            'xml_invoice_hash' => $invoiceHash,
            'ecdsa_signature' => $invoice->signedHash,
            'ecdsa_public_key' => $invoice->publicKeyBytes,
            'ecdsa_stamp_signature' => $invoice->certificateSignature,
        ]);
    }

    private static function accountingSupplierParty(string $country, string $vat, string $organizationName, object $accountingSupplierParty): Party
    {
        $partyIdentification = null;
        $postalAddress = $accountingSupplierParty->postal_address;

        if (isset($accountingSupplierParty->party_identification)) {
            $partyIdentification = new PartyIdentification(
                id: $accountingSupplierParty->party_identification->id,
            );
        }

        return new Party(
            postalAddress: new PostalAddress(
                streetName: $postalAddress->street_name,
                buildingNumber: $postalAddress->building_number,
                plotIdentification: $postalAddress->plot_identification,
                citySubdivisionName: $postalAddress->city_subdivision_name,
                cityName: $postalAddress->city_name,
                postalZone: $postalAddress->postal_zone,
                countrySubentity: $postalAddress->country_subentity,
                additionalStreetName: $postalAddress->additional_street_name,
                countryIdentificationCode: $country
            ),
            partyTaxScheme: new PartyTaxScheme(
                companyId: $vat
            ),
            partyLegalEntity: new PartyLegalEntity(
                registrationName: $organizationName
            ),
            partyIdentification: $partyIdentification,
        );
    }

    private static function accountingCustomerParty(object $accountingSupplierParty): Party
    {
        $postalAddress = $accountingSupplierParty->postal_address;

        return new Party(
            postalAddress: new PostalAddress(
                streetName: $postalAddress->street_name ?? null,
                buildingNumber: $postalAddress->building_number ?? null,
                plotIdentification: $postalAddress->plot_identification ?? null,
                citySubdivisionName: $postalAddress->city_subdivision_name ?? null,
                cityName: $postalAddress->city_name ?? null,
                postalZone: $postalAddress->postal_zone ?? null,
                countrySubentity: $postalAddress->country_subentity ?? null,
                additionalStreetName: $postalAddress->additional_street_name ?? null,
                countryIdentificationCode: $postalAddress->country_identification_code,
            ),
            partyTaxScheme: new PartyTaxScheme(
                companyId: $accountingSupplierParty->party_tax_scheme->company_id ?? null,
            ),
            partyLegalEntity: new PartyLegalEntity(
                registrationName: $accountingSupplierParty->party_legal_entity->registration_name,
            ),
            partyIdentification: isset($accountingSupplierParty->party_identification)
                ? new PartyIdentification(
                    id: $accountingSupplierParty->party_identification->id,
                    schemeId: $accountingSupplierParty->party_identification->scheme_id ?? 'CRN',
                )
                : null
        );
    }

    private static function allowanceCharges(array $allowanceCharges): array
    {
        $charges = [];

        foreach ($allowanceCharges as $allowanceCharge) {
            $tax = [];

            foreach ($allowanceCharge->tax_categories as $taxCategory) {
                $tax[] = new TaxCategory(taxPercent: $taxCategory->tax_percent);
            }

            $charges[] = new AllowanceCharge(
                chargeIndicator: $allowanceCharge->charge_indicator,
                allowanceChargeReason: $allowanceCharge->allowance_charge_reason,
                amount: $allowanceCharge->amount,
                currencyID: $allowanceCharge->currency_id,
                taxCategories: $tax
            );
        }

        return $charges;
    }

    private static function taxTotals(array $taxTotals): array
    {
        $result = [];

        foreach ($taxTotals as $taxTotal) {
            $result[] = new TaxTotal(
                taxAmount: $taxTotal->tax_amount,
                taxSubtotalAmount: $taxTotal->tax_subtotal_amount ?? null,
                taxableAmount: $taxTotal->taxable_amount ?? null,
                taxCategory: isset($taxTotal->tax_category)
                    ? new TaxCategory(
                        taxPercent: $taxTotal->tax_category->tax_percent,
                        taxExemptionReasonCode: $taxTotal->tax_exemption_reason_code ?? null,
                        taxExemptionReason: $taxTotal->tax_exemption_reason ?? null,
                    )
                    : null
            );
        }

        return $result;
    }

    private static function legalMonetaryTotal(object $data): LegalMonetaryTotal
    {
        return new LegalMonetaryTotal(
            lineExtensionAmount: $data->line_extension_amount, // BT-101
            taxExclusiveAmount: $data->tax_exclusive_amount, // BT-102
            taxInclusiveAmount: $data->tax_inclusive_amount, // BT-112
            allowanceTotalAmount: $data->allowance_total_amount, // BT-114
            prepaidAmount: $data->prepaid_amount,
            payableAmount: $data->payable_amount, // BT-115
        );
    }

    private static function invoiceLines(array $lines): array
    {
        $result = [];

        foreach ($lines as $idx => $line) {
            $result[] = new InvoiceLine(
                invoicedQuantity: (string)$line->invoiced_quantity,
                invoicedQuantityUnitCode: $line->invoiced_quantity_unit_code,
                lineExtensionAmount: $line->line_extension_amount,
                taxTotal: new TaxTotal(
                    taxAmount: $line->tax_total->tax_amount,
                    roundingAmount: $line->tax_total->rounding_amount
                ),
                item: new Item(
                    name: $line->item->name
                ),
                price: new Price(
                    priceAmount: $line->price->price_amount,
                    allowanceCharge: new AllowanceCharge(
                        chargeIndicator: $line->price->allowance_charge->charge_indicator,
                        allowanceChargeReason: $line->price->allowance_charge->allowance_charge_reason,
                        amount: $line->price->allowance_charge->amount,
                        currencyID: $line->price->allowance_charge->currency_id,
                        taxCategory: $line->price->allowance_charge->tax_category ?? null,
                        addTaxCategory: $line->price->allowance_charge->add_tax_category,
                        addID: $line->price->allowance_charge->add_id,
                        taxCategories: $line->price->allowance_charge->tax_categories,
                        index: $idx,
                    )
                ),
                idIdx: $idx,
            );
        }

        return $result;
    }
}
