<?php

require '../vendor/autoload.php';

use ZATCA\UBL\CommonAggregateComponents\{
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
    Price,
};
use ZATCA\Tags;
use ZATCA\UBL\InvoiceSubtypeBuilder;
use ZATCA\UBL\Invoice;
use ZATCA\Client;
use ZATCA\Mode;

// ===============================
// Configuration and Credentials
// ===============================

$environment = Mode::Sim;

$privateKeyPath = __DIR__ . '/ec-secp256k1-priv-key.pem';
$complianceCertificatePath = __DIR__ . '/pro-certificate.pem';
$complianceResponsePath = __DIR__ . '/pro-response.json';

$credentials = json_decode(file_get_contents($complianceResponsePath), true);
$username = $credentials['binarySecurityToken'];
$password = $credentials['secret'];
$complianceRequestId = $credentials['requestID'];

// ===============================
// Setup Invoice Values
// ===============================

$invoiceId = "SME00010";
$invoiceUuid = "f2f49322-f77d-4b1f-84e6-f02bed8adbe7";
$note = "ABC";
$noteLanguageId = "ar";
$issueDate = date('Y-m-d');
$issueTime = "17:41:08";

$invoice_subtype = InvoiceSubtypeBuilder::build(
    simplified: true,
    thirdParty: false,
    nominal: false,
    exports: false,
    summary: false,
    selfBilled: false
);

echo $invoice_subtype . PHP_EOL;

$payment_means_code = Invoice::PAYMENT_MEANS['bank_card'];
$invoice_type = Invoice::TYPES['invoice']; // ['invoice', 'debit', 'credit']

$invoiceCounterValue = "10";
$previousInvoiceHash = "NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==";
$currencyCode = "SAR";

$csrOptions = json_decode(file_get_contents('csr_options.json'), true);
$vatNo = $csrOptions['organization_identifier'];

$accountingSupplierParty = new Party(
    postalAddress: new PostalAddress(
        streetName: "شارع خالد بن الوليد",
        buildingNumber: "2568",
        plotIdentification: "6273",
        citySubdivisionName: "الشرقية",
        cityName: "الطائف",
        postalZone: "26523",
        countrySubentity: null,
        additionalStreetName: null,
        countryIdentificationCode: "SA"
    ),
    partyTaxScheme: new PartyTaxScheme(
        companyId: $vatNo
    ),
    partyLegalEntity: new PartyLegalEntity(
        registrationName: 'alalam alfani Security Est'
    ),
    partyIdentification: new PartyIdentification(
        id: "324223432432432"
    ),
);

$accountingCustomerParty = new Party(
    postalAddress: new PostalAddress(
        streetName: "طريق الملك فهد",
        buildingNumber: "5566",
        plotIdentification: "7890",
        citySubdivisionName: "حي العليا",
        cityName: "الرياض | Riyadh",
        postalZone: "11564",
        countrySubentity: "منطقة الرياض",
        additionalStreetName: "بجوار البنك الأهلي",
        countryIdentificationCode: "SA"
    ),
    partyTaxScheme: new PartyTaxScheme(
        companyId: '310123456700003'
    ),
    partyLegalEntity: new PartyLegalEntity(
        registrationName: "مؤسسة العالم الفني الامنية"
    ),
    partyIdentification: new PartyIdentification(
        id: "987654321098765"
    )
);

if ('0100000' === $invoice_subtype) {
    $delivery = new \ZATCA\UBL\CommonAggregateComponents\Delivery(
        actualDeliveryDate: $issueDate
    );
} else {
    $delivery = null;
}

$allowanceCharges = [
    new AllowanceCharge(
        chargeIndicator: false,
        allowanceChargeReason: "discount",
        amount: "0.00",
        currencyID: "SAR",
        taxCategories: [
            new TaxCategory(taxPercent: "15"),
            new TaxCategory(taxPercent: "15")
        ]
    )
];

$taxTotals = [
    new TaxTotal(
        taxAmount: "30.15",
    ),
    new TaxTotal(
        taxAmount: "30.15",
        taxSubtotalAmount: "30.15",
        taxableAmount: "201.00",
        taxCategory: new TaxCategory(
            taxPercent: "15.00"
        )
    )
];

$legalMonetaryTotal = new LegalMonetaryTotal(
    lineExtensionAmount: "201.00",
    taxExclusiveAmount: "201.00",
    taxInclusiveAmount: "231.15",
    allowanceTotalAmount: "0.00",
    prepaidAmount: "0.00",
    payableAmount: "231.15"
);

$invoiceLines = [
    new InvoiceLine(
        invoicedQuantity: "33.000000",
        invoicedQuantityUnitCode: "PCE",
        lineExtensionAmount: "99.00",
        taxTotal: new TaxTotal(
            taxAmount: "14.85",
            roundingAmount: "113.85"
        ),
        item: new Item(name: "كتاب"),
        price: new Price(
            priceAmount: "3.00",
            allowanceCharge: new AllowanceCharge(
                chargeIndicator: false,
                allowanceChargeReason: "discount",
                amount: "0.00",
                currencyID: 'SAR',
                taxCategory: null,
                addTaxCategory: false,
                addID: false,
                taxCategories: [],
                index: null,
            )
        ),
        idIdx: 1
    ),
    new InvoiceLine(
        invoicedQuantity: "3.000000",
        invoicedQuantityUnitCode: "PCE",
        lineExtensionAmount: "102.00",
        taxTotal: new TaxTotal(
            taxAmount: "15.30",
            roundingAmount: "117.30"
        ),
        item: new Item(name: "قلم"),
        price: new Price(
            priceAmount: "34.00",
            allowanceCharge: new AllowanceCharge(
                chargeIndicator: false,
                allowanceChargeReason: "discount",
                amount: "0.00",
                currencyID: 'SAR',
                taxCategory: null,
                addTaxCategory: false,
                addID: false,
                taxCategories: [],
                index: null,
            )
        ),
        idIdx: 2
    )
];

/*
 * 3. Construct the Invoice
 */

$invoice = new Invoice(
    id: $invoiceId,
    uuid: $invoiceUuid,
    issueDate: $issueDate,
    issueTime: $issueTime,
    subtype: $invoice_subtype,
    type: $invoice_type,
    invoiceCounterValue: $invoiceCounterValue,
    accountingSupplierParty: $accountingSupplierParty,
    accountingCustomerParty: $accountingCustomerParty,
    paymentMeansCode: $payment_means_code,
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
    instructionNote: "Refunded because customer ordered by mistake",

    # Make sure to follow this format as it is what ZATCA expects
    billingReference: "Invoice Number: 354; Invoice Issue Date: 2021-02-10",
);

$invoiceTimestamp = "{$invoice->issueDate}T{$invoice->issueTime}";

$invoice->sign(
    privateKey: base64_encode(file_get_contents($privateKeyPath)),
    certificate: base64_encode(file_get_contents($complianceCertificatePath)),
    signingTime: $invoiceTimestamp,
);

$invoiceHash = $invoice->generateHash();

$tags = new Tags([
    'seller_name' => $csrOptions['common_name'],
    'vat_registration_number' => $vatNo,
    'timestamp' => $invoiceTimestamp,
    'invoice_total' => $legalMonetaryTotal->payableAmount,
    'vat_total' => 30.15,
    'xml_invoice_hash' => $invoiceHash,
    'ecdsa_signature' => $invoice->signedHash,
    'ecdsa_public_key' => $invoice->publicKeyBytes,
    'ecdsa_stamp_signature' => $invoice->certificateSignature,
]);

// Generate QR code base64
$invoice->qrCode = $tags->toBase64();

$client = new Client(
    username: $username,
    password: $password,
    mode: $environment,
    language: 'en',
    //    verbose: true,
);

echo $invoiceHash . PHP_EOL;

$invc = [
    "invoiceHash" => $invoiceHash,
    "uuid" => $invoice->uuid,
    "invoice" => $invoice->toBase64(true),
];

file_put_contents('final_invoice_php.xml', base64_decode($invc['invoice']));

echo "compliance_check" . PHP_EOL;
$response = $client->compliance_check($invc['uuid'], $invc['invoiceHash'], $invc['invoice']);
print_r($response);

if ('0200000' === $invoice_subtype) {
    echo "report_invoice" . PHP_EOL;
    $response = $client->report_invoice($invc['uuid'], $invc['invoiceHash'], $invc['invoice'], true);
    print_r($response);
} else {
    echo "clear_invoice" . PHP_EOL;
    // Standard Invoices (B2B): Use the clearance endpoint.
    $response = $client->clear_invoice($invc['uuid'], $invc['invoiceHash'], $invc['invoice'], true);
    print_r($response);
}
