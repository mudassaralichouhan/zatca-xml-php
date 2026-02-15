<?php

use ZATCA\Client;
use ZATCA\Mode;

require '../vendor/autoload.php';

$environment = Mode::Sim;

// File paths
$productionCertificatePath = __DIR__ . '/pro-certificate.pem';
$productionResponsePath = __DIR__ . '/pro-response.json';
$privateKeyPath = __DIR__ . '/ec-secp256k1-priv-key.pem';
$complianceResponsePath = __DIR__ . '/compliance-response.json';

// 1) Load your compliance CSID credentials
$complianceJson = file_get_contents($complianceResponsePath);
$creds = json_decode($complianceJson, true);
$username = $creds['binarySecurityToken'] ?? throw new RuntimeException("Missing binarySecurityToken");
$password = $creds['secret'] ?? throw new RuntimeException("Missing secret");
$complianceRequestId = $creds['requestID'] ?? throw new RuntimeException("Missing requestID");

$client = new Client(
    username: $username,
    password: $password,
    mode: $environment,
);

// The compliance certificate is not done with the following compliance steps yet
// [standard-compliant, standard-credit-note-compliant, standard-debit-note-compliant, simplified-compliant, simplified-credit-note-compliant, simplified-debit-note-compliant]
$response = $client->issue_production_csid($complianceRequestId);

if (($response['dispositionMessage'] ?? '') === 'ISSUED') {
    echo "✅ Production CSID issued successfully!\n";

    $binarySecurityToken = $response['binarySecurityToken'];
    $secret = $response['secret'];
    $requestID = $response['requestID'];

    echo "CSID issued successfully!\n";
    echo "Binary Security Token (Username): {$binarySecurityToken}\n";
    echo "Secret (Password): {$secret}\n";
    echo "Request ID: {$requestID}\n";

    file_put_contents($productionResponsePath, json_encode($response, JSON_PRETTY_PRINT));

    $decoded_certificate = base64_decode($binarySecurityToken);
    $pem_certificate = "-----BEGIN CERTIFICATE-----\n" . $decoded_certificate . "\n-----END CERTIFICATE-----";

    file_put_contents($productionCertificatePath, $pem_certificate);
    echo "Certificate saved to {$productionCertificatePath}\n";
} else {
    // error handling
    echo "❌ Failed to issue production CSID.\n";
    if (isset($response['code'], $response['message'])) {
        echo "Error Code: {$response['code']}\n";
        echo "Message: {$response['message']}\n";
    }
    if (!empty($response['errors'])) {
        echo "Details: " . json_encode($response['errors'], JSON_UNESCAPED_SLASHES) . "\n";
    }
}
