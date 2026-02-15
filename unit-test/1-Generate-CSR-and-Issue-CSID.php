<?php

require_once '../vendor/autoload.php';

const ROOT_PATH = __DIR__;

use ZATCA\Signing\CSR;
use ZATCA\Client;
use ZATCA\Mode;
use ZATCA\Signing\ECDSA;

$environment = Mode::Sim;

// CSR options
$csrOptions = json_decode(file_get_contents('csr_options.json'), true);
//$csrOptions['egs_serial_number'] = \ZATCA\Hashing::uuidv4();

// Your VAT ID
$vat_id = $csrOptions['organization_identifier'];

// csr and private key file (in PEM format)
// openssl ecparam -name secp256k1 -genkey -noout -out ec-secp256k1-priv-key.pem
$private_key_path = ROOT_PATH . "/ec-secp256k1-priv-key.pem";
$compliance_certificate_path = ROOT_PATH . "/compliance-certificate.pem";
$compliance_response_path = ROOT_PATH . "/compliance-response.json";

$otp = "864033";

// Invoice type code: Four digits, each digit acting as a boolean.
// Order: Standard Invoice, Simplified, future use, future use
// "1100" means Standard and Simplified invoices are enabled.
$invoice_type = "1100";

$private_key = ECDSA::ecSecp256k1PrivKey();
// Generate the CSR
$generator = new CSR(
    csrOptions: $csrOptions,
    privateKeyBase64: base64_encode($private_key),
    mode: $environment,
);

// Generate the CSR in PEM format
$csr_pem = $generator->generate();

// Encode the CSR to Base64 as required by ZATCA API
$csr_base64 = base64_encode($csr_pem);

// Construct an unauthenticated API client (issue_csid is unauthenticated)
$client = new Client(
    username: "",
    password: "",
    mode: $environment,
);

// Send the CSR to ZATCA to issue a CSID
$response = $client->issue_csid(csr: $csr_base64, otp: $otp);

// Handle the response
if ($response["dispositionMessage"] === "ISSUED") {
    $binary_security_token = $response["binarySecurityToken"];
    $secret = $response["secret"];
    $request_id = $response["requestID"];

    $credentials = [
        "binarySecurityToken" => $binary_security_token,
        "secret" => $secret,
        "requestID" => $request_id
    ];

    file_put_contents($compliance_response_path, json_encode($credentials, JSON_PRETTY_PRINT));
    echo "Credentials saved to {$compliance_response_path}\n";

    $decoded_certificate = base64_decode($binary_security_token);

    $pem_certificate = "-----BEGIN CERTIFICATE-----\n" . $decoded_certificate . "\n-----END CERTIFICATE-----";

    file_put_contents($private_key_path, $private_key);
    file_put_contents($compliance_certificate_path, $pem_certificate);
    echo "Certificate saved to {$compliance_certificate_path}\n";
} else {
    echo "Failed to issue CSID.\n";
    print_r($response);

    if (isset($response['code'])) {
        echo "Error Code: {$response['code']}\n";
    }
    if (isset($response['message'])) {
        echo "Message: {$response['message']}\n";
    }
    if (isset($response['errors'])) {
        echo "Errors: " . print_r($response['errors'], true) . "\n";
    }
}
