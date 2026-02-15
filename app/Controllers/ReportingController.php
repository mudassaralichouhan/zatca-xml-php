<?php

namespace App\Controllers;

use App\Exceptions\BadRequestException;
use App\Exceptions\ValidationException;
use App\Helpers\Helper;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\InvoiceBuilderService;
use App\Services\StoreInvoiceService;
use App\Services\ValidatorService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use ZATCA\Client;
use App\Services\ApiResponse;

class ReportingController extends BaseController
{
    public function reportSimplifiedNotes(Request $request): Response
    {
        $user = $request->attributes->get('user');
        $mode = $request->attributes->get('zatca_mode');
        $modeEnum = $request->attributes->get('zatca_enum');

        $params = $request->query->all();

        if (empty($params['request_id']) || !ctype_digit((string)$params['request_id'])) {
            throw new BadRequestException([
                'message' => 'Query parameter "request_id" is required and must be an integer.',
            ]);
        }

        $requestId = (int)$params['request_id'];

        $data = ValidatorService::load($request->getContent(), 'zatca.note.json');
        $data->invoice_sub_type->simplified = true;
        $data->delivery = null;

        if ($data->invoice_type != 'invoice') {
            $inv = DB::table('invoices')
                ->select(['issue_date'])
                ->where('invoice_id', $data->billing_reference->invoice_id)
                ->where(['user_id' => $user->id])
                ->first();

            if (!$inv) {
                throw new ValidationException([
                    'path' => '/billing_reference/invoice_id',
                    'message' => "Invoice not found {$data->billing_reference->invoice_id}"
                ]);
            }

            $data->billing_reference->issue_date = $inv->issue_date;
        }

        $fetchCsrComplianceData = StoreInvoiceService::fetchCsrComplianceData($requestId);
        $csrOptions = $fetchCsrComplianceData['csr_options'];

        $base = DB::table('invoices')
            ->where('user_id', $user->id)
            ->where('simplified', '1');

        $last = DB::table('invoices')
            ->where('user_id', $user->id)
            ->where('simplified', '1')
            ->orderBy('id', 'desc')
            ->select('hash')
            ->first();

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
            privateKeyBase64: $fetchCsrComplianceData['private_key_base64'],
            CertificateBase4: $fetchCsrComplianceData['csr_base64'],
            payload: $data,
            simplified: $data->invoice_sub_type->simplified,
            invoiceType: $data->invoice_type,
            invoiceCounterValue: $base->count() + 1,
            previousInvoiceHash: $last ? $last->hash : base64_encode('0'),
        );

        $type = $data->invoice_type;
        $subType = $data->invoice_sub_type->simplified === true ? 'simplified' : 'standard';

        // invoice must be unique
        ValidatorService::isExist($invoiceId, $user->id);

        $client = new Client(
            username: $fetchCsrComplianceData['binary_security_token'],
            password: $fetchCsrComplianceData['secret'],
            mode: $modeEnum,
        );

        $result = $client->report_invoice(
            uuid: $invoiceUUID,
            invoiceHash: $invoiceHash,
            invoice: $invoiceBase64,
            cleared: true,
        );

        if (!is_array($result)) {
            throw new BadRequestException([
                'request_id' => $requestId,
                'message' => 'CSID reporting for simplified has been expired',
                'zatca' => $result,
            ]);
        }

        if (($result['reportingStatus'] ?? '') === 'REPORTED') {

            DB::beginTransaction();

            //    $url = MinioStorageService::uploadXml(
            //        bucket: $_ENV['MINIO_BUCKET'],
            //        invoiceId: $invoiceId,
            //        base64Xml: $unsignedXmlBase64Encoded
            //    );

            DB::table('invoices')
                ->updateOrInsert([
                    'invoice_id' => $invoiceId,
                    'user_id' => $user->id,
                ], [
                    'request_id' => $requestId,
                    'vat_id' => $csrOptions->organization_identifier,
                    'hash' => $invoiceHash,
                    'uuid' => $invoiceUuid,
                    'note' => $data->note,
                    'note_language_id' => $data->note_language_id,
                    'issue_date' => $data->issue_date,
                    'issue_time' => $data->issue_time,
                    'simplified' => $data->invoice_sub_type->simplified,
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
                    'instruction_note' => $data->instruction_note ?? null,
                    'billing_reference' => json_encode($data->billing_reference) ?? null,
                    'bucket_key' => null/*$url*/,
                ]);

            $invoiceRow = DB::table('invoices')
                ->select(['id'])
                ->where([
                    'invoice_id' => $invoiceId,
                    'user_id' => $user->id,
                    'uuid' => $invoiceUuid,
                ])
                ->first();

            DB::table('invoice_data')
                ->updateOrInsert([
                    'invoice_id' => $invoiceRow->id,
                ], [
                    'base64_encoded' => $unsignedXmlBase64Encoded,
                    'base64_qr' => $qr,
                    'zatca_response' => json_encode($result),
                ]);

            foreach ($data->invoice_lines as $line) {
                DB::table('invoice_lines')
                    ->insert([
                        'invoice_id' => $invoiceRow->id,
                        'invoiced_quantity' => $line->invoiced_quantity,
                        'data' => json_encode($line),
                    ]);
            }

            DB::commit();
            // DB::rollBack();

            return ApiResponse::success([
                'invoice_id' => $invoiceId,
                'hash' => $invoiceHash,
                'uuid' => $invoiceUuid,
                'qr' => $qr,
                // 'unsigned_invoice' => /*MinioStorageService::getObjectUrl($url)*/,
                'unsigned_invoice' => $unsignedXmlBase64Encoded,
                'zatca' => [
                    'warning' => $result['validationResults']['warningMessages'],
                    'error' => $result['validationResults']['errorMessages'],
                    'validation' => $result['validationResults']['status'],
                    'status' => $result['reportingStatus'],
                ]
            ], 201);
        }

        $response = [
            'message' => "ZATCA {$subType} {$type} failed",
            'zatca' => [
                'warning' => $result['validationResults']['warningMessages'] ?? null,
                'error' => $result['validationResults']['errorMessages'] ?? null,
                'validation' => $result['validationResults']['status'] ?? null,
                'status' => $result['reportingStatus'] ?? null,
            ],
        ];

        if (!Helper::isProduction()) {
            $response['qr'] = $qr;
            $response['unsigned_invoice'] = $unsignedXmlBase64Encoded;
        }

        return ApiResponse::error(400, $response);
    }
}
