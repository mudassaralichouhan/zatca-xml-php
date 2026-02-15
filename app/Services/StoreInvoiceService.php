<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use App\Exceptions\ValidationException;
use App\Logging\Logger;

class StoreInvoiceService
{
    public static function store(string $invoiceId, int $userId)
    {
    }

    public static function fetchCsrComplianceData(string $requestId): array
    {
        $csrCompliance = DB::table('csr_production as p')
            ->leftJoin('csr_compliance as c', 'p.csr_compliance_id', '=', 'c.id')
            ->select([
                'p.request_id',
                'p.binary_security_token',
                'p.secret',
                'p.csr_options_id',
                'p.csr_base64',
                'c.private_key_base64',
            ])
            ->whereNull('p.deleted_at')
            ->where('p.request_id', $requestId)
            ->first();

        if (!$csrCompliance) {
            throw new ValidationException([
                'path' => '/request_id',
                'message' => 'No csr_production CSID found for the given request_id.'
            ]);
        }

        $csrOptions = DB::table('csr_options')
            ->where('id', $csrCompliance->csr_options_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$csrOptions) {
            throw new ValidationException([
                'path' => '/request_id',
                'message' => 'No compliant CSR options found for the given request_id.'
            ]);
        }

        return [
            'binary_security_token' => $csrCompliance->binary_security_token,
            'secret' => $csrCompliance->secret,
            'csr_base64' => $csrCompliance->csr_base64,
            'private_key_base64' => $csrCompliance->private_key_base64,
            'csr_options' => $csrOptions,
        ];
    }
}
