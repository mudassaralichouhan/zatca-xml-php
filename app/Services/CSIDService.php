<?php

namespace App\Services;

use App\Exceptions\BadRequestException;
use Illuminate\Database\Capsule\Manager as DB;
use ZATCA\Client;
use ZATCA\Signing\CSR;
use ZATCA\Signing\ECDSA;

class CSIDService
{
    public static function issueCompliance(object $data, string $otp, int $userId, \ZATCA\Mode $mode)
    {
        // https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing-Detailed-Technical-Guideline.pdf
        // Define the allowed invoice_type values based on ZATCA's Functionality Map.
        // Each 4-character string represents support for T, S, X, and Y respectively.
        // '1' indicates support; '0' indicates no support.
        // Currently, X and Y are reserved for future use and should be set to '0'.'

        //        Invoice Type Options:
        //        T – Standard Tax Invoice
        //        Used for Business-to-Business (B2B) transactions.
        //        S – Simplified Tax Invoice
        //        Used for Business-to-Consumer (B2C) transactions.
        //        X – Reserved for Future Use
        //        Currently set to 0 by default.
        //        Y – Reserved for Future Use
        //        Currently set to 0 by default.
        //
        //        Examples of Functionality Map:
        //        1000: Supports only Standard Tax Invoices.
        //        0100: Supports only Simplified Tax Invoices.
        //        1100: Supports both Standard and Simplified Tax Invoices.

        $allowed_invoice_types = [
            '1000', // Supports only Standard Tax Invoices (T)
            '0100', // Supports only Simplified Tax Invoices (S)
            '1100', // Supports both Standard and Simplified Tax Invoices (T and S)
            // Additional combinations can be added here when X and Y are utilized.
        ];

        // Validate the provided invoice_type against the allowed values.
        if (!in_array($data->invoice_type, $allowed_invoice_types, true)) {
            throw new BadRequestException([
                'path' => '/invoice_type',
                'message' => 'Invalid invoice_type; must be one of: ' . implode(', ', $allowed_invoice_types)
            ]);
        }

        $privKeyPath = base64_encode(ECDSA::ecSecp256k1PrivKey());
        $csrGenerator = new CSR(
            csrOptions: (array)$data,
            privateKeyBase64: $privKeyPath,
            mode: $mode,
        );
        $csrPem = $csrGenerator->generate();
        $csrBase64 = base64_encode($csrPem);

        $client = new Client(
            username: '',
            password: '',
            mode: $mode
        );
        $result = $client->issue_csid(
            csr: $csrBase64,
            otp: $otp
        );

        if (($result['dispositionMessage'] ?? '') === 'ISSUED') {
            $certPem = "-----BEGIN CERTIFICATE-----\n"
                . base64_decode($result['binarySecurityToken'])
                . "\n-----END CERTIFICATE-----";

            $at = date('Y-m-d H:i:s');
            DB::table('csr_options')
                ->updateOrInsert([
                    'organization_identifier' => $data->organization_identifier,
                    'egs_serial_number' => $data->egs_serial_number, // must be unique uuid
                ], [
                    'common_name' => $data->common_name,
                    'organization_name' => $data->organization_name,
                    'organization_unit' => $data->organization_unit,
                    'country' => $data->country,
                    'address' => $data->address,
                    'business_category' => $data->business_category,
                    'invoice_type' => $data->invoice_type,
                    'egs_solution_name' => $data->egs_solution_name,
                    'egs_model' => $data->egs_model,
                    'user_id' => $userId,
                    'updated_at' => $at,
                    'created_at' => $at,
                ]);

            $csrOptionsId = DB::table('csr_options')
                ->where('organization_identifier', $data->organization_identifier)
                ->where('egs_serial_number', $data->egs_serial_number)
                ->where('user_id', $userId)
                ->value('id');

            DB::table('csr_compliance')
                ->updateOrInsert([
                    'request_id' => $result['requestID'], // must be unique big int
                ], [
                    'vat_id' => $data->organization_identifier,
                    'user_id' => $userId,
                    'csr_options_id' => $csrOptionsId,

                    'csr_base64' => base64_encode($certPem),
                    'private_key_base64' => $privKeyPath,

                    'binary_security_token' => $result['binarySecurityToken'],
                    'secret' => $result['secret'],

                    'standard_compliant' => false,
                    'standard_credit_note_compliant' => false,
                    'standard_debit_note_compliant' => false,
                    'simplified_compliant' => false,
                    'simplified_credit_note_compliant' => false,
                    'simplified_debit_note_compliant' => false,

                    'updated_at' => $at,
                    'created_at' => $at,
                ]);

            return DB::table('csr_compliance')
                ->select([
                    'request_id',
                    'standard_compliant',
                    'standard_credit_note_compliant',
                    'standard_debit_note_compliant',
                    'simplified_compliant',
                    'simplified_credit_note_compliant',
                    'simplified_debit_note_compliant',
                ])
                ->where([
                    'request_id' => $result['requestID'],
                    'user_id' => $userId,
                ])
                ->first();
        }

        throw new BadRequestException($result);
    }

    public static function issueProduction(int $requestId, int $userId, \ZATCA\Mode $mode): int
    {
        $csrCompliance = DB::table('csr_compliance')
            ->where([
                'request_id' => $requestId,

                'standard_compliant' => true,
                'standard_credit_note_compliant' => true,
                'standard_debit_note_compliant' => true,
                'simplified_compliant' => true,
                'simplified_credit_note_compliant' => true,
                'simplified_debit_note_compliant' => true,
            ])
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-25 hours')))
            ->where('user_id', $userId)
            ->select([
                'id',
                'request_id',
                'binary_security_token',
                'secret',
                'csr_options_id',
                'vat_id',
            ])
            ->first();

        if (!$csrCompliance) {
            throw new BadRequestException([
                'path' => '/request_id',
                'message' => 'No fully compliant CSID found for the given request_id. Ensure all compliance checks are passed.'
            ]);
        }

        $username = $csrCompliance->binary_security_token;
        $password = $csrCompliance->secret;
        $complianceRequestId = $csrCompliance->request_id;

        $client = new Client(
            username: $username,
            password: $password,
            mode: $mode,
        );

        // The compliance certificate is not done with the following compliance steps yet.
        // standard-compliant, standard-credit-note-compliant, standard-debit-note-compliant
        // simplified-compliant, simplified-credit-note-compliant, simplified-debit-note-compliant
        $result = $client->issue_production_csid($complianceRequestId);

        if (($result['dispositionMessage'] ?? '') === 'ISSUED') {
            $certPem = "-----BEGIN CERTIFICATE-----\n"
                . base64_decode($result['binarySecurityToken'])
                . "\n-----END CERTIFICATE-----";

            $at = date('Y-m-d H:i:s');
            DB::table('csr_production')
                ->updateOrInsert([
                    'request_id' => $result['requestID'], // must be unique big int
                ], [
                    'vat_id' => $csrCompliance->vat_id,
                    'user_id' => $userId,
                    'csr_options_id' => $csrCompliance->csr_options_id,
                    'csr_compliance_id' => $csrCompliance->id,

                    'csr_base64' => base64_encode($certPem),

                    'binary_security_token' => $result['binarySecurityToken'],
                    'secret' => $result['secret'],

                    'updated_at' => $at,
                    'created_at' => $at,
                ]);

            return (int)$result['requestID'];
        }

        throw new BadRequestException($result);
    }
}
