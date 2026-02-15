<?php

namespace ZATCA\Signing;

use ZATCA\Mode;
use Exception;

class CSR
{
    private array  $csrOptions;
    private Mode   $mode;
    private ?string $privateKeyBase64;
    private ?string $privateKeyPassword;
    private ?string $csrConfigPath = null;

    public function __construct(
        array   $csrOptions,
        ?string $privateKeyBase64,
        Mode    $mode = Mode::Pro,
        ?string $privateKeyPassword = null
    ) {
        $this->csrOptions         = array_merge($this->defaultCsrOptions(), $csrOptions);
        $this->mode               = $mode;
        $this->privateKeyBase64   = $privateKeyBase64;
        $this->privateKeyPassword = $privateKeyPassword;
    }

    public function generate(): string
    {
        if (empty($this->privateKeyBase64)) {
            throw new Exception("No private key provided");
        }

        $pem = base64_decode($this->privateKeyBase64, true);

        if ($pem === false) {
            throw new Exception("Invalid Base64 for private key");
        }
        $privKey = openssl_pkey_get_private($pem, $this->privateKeyPassword);
        if ($privKey === false) {
            throw new Exception("Failed to load private key: " . openssl_error_string());
        }

        $dn = [
            'commonName'            => $this->csrOptions['common_name'],
            'organizationalUnitName' => $this->csrOptions['organization_unit'],
            'organizationName'      => $this->csrOptions['organization_name'],
            'countryName'           => $this->csrOptions['country'],
        ];

        $this->csrConfigPath = sys_get_temp_dir() . '/csr_' . uniqid() . '.conf';
        file_put_contents($this->csrConfigPath, $this->generateCsrConfig());

        $configArgs = [
            'config'         => $this->csrConfigPath,
            'req_extensions' => 'v3_req',
            'digest_alg'     => 'sha256',
        ];
        $csrResource = openssl_csr_new($dn, $privKey, $configArgs);
        if ($csrResource === false) {
            throw new Exception("openssl_csr_new failed: " . openssl_error_string());
        }

        if (!openssl_csr_export($csrResource, $csrPem)) {
            throw new Exception("openssl_csr_export failed: " . openssl_error_string());
        }

        @unlink($this->csrConfigPath);

        return $csrPem;
    }

    private function defaultCsrOptions(): array
    {
        return [
            'common_name'             => '',
            'organization_identifier' => '',
            'organization_name'       => '',
            'organization_unit'       => '',
            'country'                 => 'SA',
            'invoice_type'            => '1100',
            'address'                 => '',
            'business_category'       => '',
            'egs_solution_name'       => '',
            'egs_model'               => '',
            'egs_serial_number'       => ''
        ];
    }

    private function certEnvironment(): string
    {
        return match ($this->mode) {
            Mode::Pro => 'ZATCA-Code-Signing',
            Mode::Sim => 'PREZATCA-Code-Signing',
            Mode::Dev => 'TSTZATCA-Code-Signing',
            default    => throw new Exception("Unknown environment: {$this->mode}")
        };
    }

    private function egsSerialNumber(): string
    {
        return "1-{$this->csrOptions['egs_solution_name']}|2-{$this->csrOptions['egs_model']}|3-{$this->csrOptions['egs_serial_number']}";
    }

    private function generateCsrConfig(): string
    {
        $env      = $this->certEnvironment();
        $serial   = $this->egsSerialNumber();
        $uid      = $this->csrOptions['organization_identifier'];
        $title    = $this->csrOptions['invoice_type'];
        $address  = $this->csrOptions['address'];
        $business = $this->csrOptions['business_category'];

        return <<<CONF
[ req ]
prompt             = no
utf8               = no
distinguished_name = dn_sect
req_extensions     = v3_req

[ v3_req ]
1.3.6.1.4.1.311.20.2 = ASN1:PRINTABLESTRING:$env
subjectAltName     = dirName:dir_sect

[ dir_sect ]
SN                = $serial
UID               = $uid
title             = $title
registeredAddress = $address
businessCategory  = $business

[ dn_sect ]
commonName               = {$this->csrOptions['common_name']}
organizationalUnitName   = {$this->csrOptions['organization_unit']}
organizationName         = {$this->csrOptions['organization_name']}
countryName              = {$this->csrOptions['country']}
CONF;
    }
}
