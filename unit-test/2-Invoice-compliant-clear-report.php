<?php

require '../vendor/autoload.php';
require 'build-invoice.php';

use ZATCA\Client;
use ZATCA\Mode;

$environment = Mode::Sim;
$privateKeyPath = base64_encode(file_get_contents(__DIR__ . '/ec-secp256k1-priv-key.pem'));
$certificate = true;

if ($certificate) {
    $complianceCertificatePath = base64_encode(file_get_contents(__DIR__ . '/pro-certificate.pem'));
    $complianceResponsePath = __DIR__ . '/pro-response.json';
} else {
    $complianceCertificatePath = base64_encode(file_get_contents(__DIR__ . '/compliance-certificate.pem'));
    $complianceResponsePath = __DIR__ . '/compliance-response.json';
}

$csrOptions = json_decode(file_get_contents('csr_options.json'), true);

// standard-compliant,standard-credit-note-compliant,standard-debit-note-compliant,
// simplified-compliant,simplified-credit-note-compliant,simplified-debit-note-compliant
foreach (['invoice', 'debit', 'credit'] as $type) {
    list($invoice, $invoiceHash) = $invoice = buildInvoice(
        csrOptions: $csrOptions,
        privateKeyBase64: $privateKeyPath,
        CertificateBase4: $complianceCertificatePath,
        simplified: false,
        invoiceType: $type,
    );

    echo $invoiceHash . PHP_EOL;
    $credentials = json_decode(file_get_contents($complianceResponsePath), true);
    $username = $credentials['binarySecurityToken'];
    $password = $credentials['secret'];
    $complianceRequestId = $credentials['requestID'];


    $client = new Client(
        username: $username,
        password: $password,
        mode: $environment,
        language: 'en',
        //    verbose: true,
    );

    $encodeInvoice = $invoice->toBase64(true);

    file_put_contents('final_invoice_php.xml', base64_decode($encodeInvoice));

    if ($invoice->type === '388') {
        // invoice
        echo ($invoice->subtype === '0200000' ? 'Simplified Invoice' : 'Standard Invoice') . PHP_EOL;
    } elseif ($invoice->type === '383') {
        // debit
        echo ($invoice->subtype === '0200000' ? 'Simplified debit' : 'Standard debit') . PHP_EOL;
    } elseif ($invoice->type === '381') {
        // credit
        echo ($invoice->subtype === '0200000' ? 'Simplified credit' : 'Standard credit') . PHP_EOL;
    }

    echo 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation/' . PHP_EOL;
    if ($certificate) {
        if ('0200000' === $invoice->subtype) {
            echo "invoices/reporting/single" . PHP_EOL;
            $response = $client->report_invoice($invoice->uuid, $invoiceHash, $encodeInvoice, true);
        } else {
            echo "invoices/clearance/single" . PHP_EOL;
            // Standard Invoices (B2B): Use the clearance endpoint.
            $response = $client->clear_invoice($invoice->uuid, $invoiceHash, $encodeInvoice, true);
        }
    } else {
        echo "compliance/invoices" . PHP_EOL;
        $response = $client->compliance_check($invoice->uuid, $invoiceHash, $encodeInvoice);
    }

    print_r($response);
}
