<?php

namespace ZATCA;

class Client
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $language = 'ar';
    private string $version = 'V2';
    private bool $verbose = false;

    public function __construct(
        string $username = '',
        string $password = '',
        Mode   $mode = Mode::Pro,
        string $language = 'ar',
        bool   $verbose = false
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->language = $language;
        $this->verbose = $verbose;

        $this->baseUrl = match ($mode) {
            Mode::Pro => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core/',
            Mode::Sim => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation/',
            Mode::Dev => 'https://gw-apic-gov.gazt.gov.sa/e-invoicing/developer-portal/',
        };
    }

    private function request(
        string $method,
        string $path,
        array  $body = [],
        array  $headers = [],
        bool   $authenticated = true
    ): array|string {
        $url = $this->baseUrl . ltrim($path, '/');
        $ch = curl_init($url);

        // Default headers
        $allHeaders = [
            "Accept-Language: {$this->language}",
            "Content-Type: application/json",
            "Accept-Version: {$this->version}",
        ];
        foreach ($headers as $key => $value) {
            $allHeaders[] = "$key: $value";
        }

        if ($this->verbose) {
            echo "Request: $method $url\n";
            echo "Headers: " . implode('; ', $allHeaders) . "\n";
            echo "Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
        ]);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        if ($authenticated && $this->username !== '' && $this->password !== '') {
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        }

        $responseBody = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($this->verbose) {
            echo "Response Code: $statusCode\n";
            echo "Response Body: $responseBody\n";
        }

        if ($responseBody === false) {
            return ['message' => 'Network error', 'error' => $curlError ?: 'unknown'];
        }

        if ($statusCode >= 400) {
            return [
                'message' => 'HTTP error',
                'status' => $statusCode,
                'body' => $responseBody,
            ];
        }

        if (is_string($contentType) && str_contains($contentType, 'application/json')) {
            $decoded = json_decode($responseBody, true);
            return $decoded !== null ? $decoded : $responseBody;
        }

        return $responseBody;
    }

    public function issue_csid(string $csr, string $otp): array|string
    {
        return $this->request(
            method: 'POST',
            path: 'compliance',
            body: ['csr' => $csr],
            headers: ['OTP' => $otp],
            authenticated: false
        );
    }

    public function issue_production_csid(string $complianceRequestId): array|string
    {
        return $this->request(
            method: 'POST',
            path: 'production/csids',
            body: ['compliance_request_id' => $complianceRequestId]
        );
    }

    public function renew_production_csid(string $otp, string $csr): array|string
    {
        return $this->request(
            method: 'PATCH',
            path: 'production/csids',
            body: ['csr' => $csr],
            headers: ['OTP' => $otp]
        );
    }

    public function report_invoice(string $uuid, string $invoiceHash, string $invoice, bool $cleared): array|string
    {
        return $this->request(
            method: 'POST',
            path: 'invoices/reporting/single',
            body: [
                'uuid' => $uuid,
                'invoiceHash' => $invoiceHash,
                'invoice' => $invoice
            ],
            headers: ['Clearance-Status' => $cleared ? '1' : '0']
        );
    }

    public function clear_invoice(string $uuid, string $invoiceHash, string $invoice, bool $cleared): array|string
    {
        return $this->request(
            method: 'POST',
            path: 'invoices/clearance/single',
            body: [
                'uuid' => $uuid,
                'invoiceHash' => $invoiceHash,
                'invoice' => $invoice
            ],
            headers: ['Clearance-Status' => $cleared ? '1' : '0']
        );
    }

    public function compliance_check(string $uuid, string $invoiceHash, string $invoice): array|string
    {
        return $this->request(
            method: 'POST',
            path: 'compliance/invoices',
            body: [
                'uuid' => $uuid,
                'invoiceHash' => $invoiceHash,
                'invoice' => $invoice
            ],
        );
    }
}
