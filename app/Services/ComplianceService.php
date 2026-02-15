<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use App\Exceptions\BadRequestException;
use App\Exceptions\ValidationException;
use ZATCA\Client;

class ComplianceService
{
    public static function check(int $requestId, object $data, int $userId, bool $simplified, \ZATCA\Mode $mode)
    {

        $csrCompliance = DB::table('csr_compliance')
            ->select([
                'request_id', 'binary_security_token', 'secret',
                'csr_options_id', 'csr_base64', 'private_key_base64',
            ])
            ->where(['request_id' => $requestId])
            ->where(['user_id' => $userId])
            ->whereNull('deleted_at')
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-25 hours')))
            ->first();

        if (!$csrCompliance) {
            throw new ValidationException([
                'path' => '/request_id',
                'message' => 'No csr_compliance CSID found for the given request_id.'
            ]);
        }

        $csrOptions = DB::table('csr_options')
            ->where(['id' => $csrCompliance->csr_options_id])
            ->where(['user_id' => $userId])
            ->whereNull('deleted_at')
            ->first();

        $data->invoice_sub_type->simplified = $simplified;
        $data->issue_date = date('Y-m-d');
        $data->issue_time = date('H:i:s');

        $complianceData = [];

        foreach (['invoice', 'credit', 'debit'] as $type) {
            [
                $invoiceUUID,
                $invoiceHash,
                $invoiceBase64,
                $invoiceId,
                $invoiceUuid,
                $qr,
                $unsignedXmlBase64Encoded,
            ] = InvoiceBuilderService::build(
                csrOptions: $csrOptions,
                privateKeyBase64: $csrCompliance->private_key_base64,
                CertificateBase4: $csrCompliance->csr_base64,
                payload: $data,
                simplified: $data->invoice_sub_type->simplified,
                invoiceType: $data->invoice_type = $type,
                invoiceCounterValue: (int)(DB::table('compliance_invoices')->where(['user_id' => $userId])->count() + 1),
                previousInvoiceHash: (function () use ($userId) {
                    $last = DB::table('compliance_invoices')
                        ->where('user_id', $userId)
                        ->orderBy('id', 'desc')
                        ->select('hash')
                        ->first();
                    return $last ? $last->hash : base64_encode('0');
                })(),
            );


            $data->invoice_type = $type;
            $subType = $data->invoice_sub_type->simplified === true ? 'simplified' : 'standard';

            $client = new Client(
                username: $csrCompliance->binary_security_token,
                password: $csrCompliance->secret,
                mode: $mode,
            );

            $result = $client->compliance_check(
                uuid: $invoiceUUID,
                invoiceHash: $invoiceHash,
                invoice: $invoiceBase64,
            );

            if (!is_array($result)) {
                throw new BadRequestException([
                    'request_id' => $requestId,
                    'message' => 'CSID compliance has been expired',
                    'zatca' => $result,
                ]);
            }

            if ($result['reportingStatus'] === 'REPORTED' || $result['clearanceStatus'] === 'CLEARED') {
                DB::table('compliance_invoices')
                    ->updateOrInsert([
                        'invoice_id' => $invoiceId,
                        'user_id' => $userId,
                    ], [
                        'request_id' => $requestId,
                        'vat_id' => $csrOptions->organization_identifier,
                        'hash' => $invoiceHash,
                        'uuid' => $invoiceUuid,
                        'note' => $data->note,
                        'note_language_id' => $data->note_language_id,
                        'issue_date' => $data->issue_date,
                        'issue_time' => $data->issue_time,
                        'simplified' => true,
                        'sub_type' => json_encode($data->invoice_sub_type),
                        'payment_means_code' => $data->payment_means_code,
                        'invoice_type' => $data->invoice_type,
                        'currency_code' => $data->currency_code,
                        'accounting_supplier_party' => json_encode($data->accounting_supplier_party),
                        'accounting_customer_party' => json_encode($data->accounting_customer_party),
                        'delivery' => json_encode($data->delivery),
                        'allowance_charges' => json_encode($data->allowance_charges),
                        'tax_totals' => json_encode($data->tax_totals),
                        'legal_monetary_total' => json_encode($data->legal_monetary_total),
                        'invoice_lines' => json_encode($data->invoice_lines),
                        'instruction_note' => $data->instruction_note ?? null,
                        'billing_reference' => json_encode($data->billing_reference) ?? null,
                        'base64_encoded' => $unsignedXmlBase64Encoded,
                        'base64_qr' => $qr,
                    ]);

                $field = match ("{$subType}_{$type}") {
                    'simplified_invoice' => 'simplified_compliant',
                    'simplified_credit' => 'simplified_credit_note_compliant',
                    'simplified_debit' => 'simplified_debit_note_compliant',
                    'standard_invoice' => 'standard_compliant',
                    'standard_credit' => 'standard_credit_note_compliant',
                    'standard_debit' => 'standard_debit_note_compliant',
                };

                DB::table('csr_compliance')
                    ->where([
                        'request_id' => $requestId,
                        'vat_id' => $csrOptions->organization_identifier,
                        'user_id' => $userId,
                    ])
                    ->update([$field => true]);

                $complianceData[] = [
                    'invoice_id' => $invoiceId,
                    // 'hash' => $invoiceHash,
                    // 'uuid' => $invoiceUuid,
                    'qr' => $qr,
                    'type' => $type,
                    // 'unsigned_invoice' => $unsignedXmlBase64Encoded,
                    'zatca' => [
                        'warning' => $result['validationResults']['warningMessages'],
                        'error' => $result['validationResults']['errorMessages'],
                        'validation' => $result['validationResults']['status'],
                        'status' => $result['validationResults']['status'],
                    ]
                ];
            } else {
                $complianceData[] = [
                    // 'invoice_id' => $invoiceId,
                    // 'hash' => $invoiceHash,
                    // 'uuid' => $invoiceUuid,
                    // 'qr' => $qr,
                    'type' => $type,
                    // 'unsigned_invoice' => $unsignedXmlBase64Encoded,
                    'zatca' => [
                        'warning' => $result['validationResults']['warningMessages'],
                        'error' => $result['validationResults']['errorMessages'],
                        'validation' => $result['validationResults']['status'],
                        'status' => $result['validationResults']['status'],
                    ]
                ];
            }
        }

        return $complianceData;
    }
}
