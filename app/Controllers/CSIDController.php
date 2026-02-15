<?php

namespace App\Controllers;

use App\Config\Paths;
use App\Services\ComplianceService;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\CSIDService;
use App\Services\ValidatorService;
use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Services\ApiResponse;

class CSIDController extends BaseController
{
    public function issueProductionCsidStatus(Request $request): Response
    {
        $user = $request->attributes->get('user');
        $mode = $request->attributes->get('zatca_mode');

        $data = ValidatorService::load($request->getContent(), 'csid.compliance.json');

        // Validate organization_identifier for developer-portal mode
        if ($mode === 'developer-portal' && $data->organization_identifier !== '399999999900003') {
            throw new \App\Exceptions\BadRequestException([
                'path' => '/organization_identifier',
                'message' => 'For developer-portal mode, organization_identifier must be 399999999900003'
            ]);
        }

        $modeEnum = \App\Services\ZatcaModeHeaderService::mapMode($user->zatca_mode);
        $csrCompliance = CSIDService::issueCompliance($data, $data->otp, $user->id, $modeEnum);

        $requestId = $csrCompliance->request_id;

        $invoiceData = json_decode(file_get_contents(Paths::STATIC_PATH . 'compliance.invoice.json'));

        $simplified = ComplianceService::check($requestId, $invoiceData, $user->id, true, $modeEnum);
        $standard = ComplianceService::check($requestId, $invoiceData, $user->id, false, $modeEnum);

        $proRequestId = CSIDService::issueProduction($requestId, $user->id, $modeEnum);

        return ApiResponse::success([
            'request_id' => $proRequestId,
            // 'simplified' => $simplified,
            // 'standard' => $standard,
        ], 201);
    }

    public function getComplianceCsidStatus(Request $request): Response
    {
        $user = $request->attributes->get('user');
        $vat = $request->query->get('vat');

        $csrCompliance = DB::table('csr_compliance as c')
            ->leftJoin('csr_options as o', 'c.csr_options_id', '=', 'o.id')
            ->select([
                'c.request_id',
                'o.common_name',
                'o.organization_name',
                'o.organization_unit',
                'o.invoice_type',
                'o.egs_solution_name',
                'o.egs_model',
                'o.egs_serial_number',
                'c.standard_compliant',
                'c.standard_credit_note_compliant',
                'c.standard_debit_note_compliant',
                'c.simplified_compliant',
                'c.simplified_credit_note_compliant',
                'c.simplified_debit_note_compliant',
                'c.created_at',
            ])
            ->where('c.vat_id', $vat)
            ->where('c.user_id', $user->id)
            ->where('o.user_id', $user->id)
            ->whereNull('c.deleted_at')
            ->orderBy('c.created_at', 'desc')
            ->paginate();

        $now = new DateTime();
        $nowMinus1Day = $now->sub(new DateInterval('P1D'));

        foreach ($csrCompliance->items() as $item) {
            $createdAt = new DateTime($item->created_at);
            $item->is_expired = $createdAt < $nowMinus1Day;
        }

        return ApiResponse::success(
            array_values($csrCompliance->items()),
            201,
            [
                'pagination' => [
                    'current_page' => $csrCompliance->currentPage(),
                    'first_page_url' => $csrCompliance->url(1),
                    'from' => $csrCompliance->firstItem(),
                    'last_page' => $csrCompliance->lastPage(),
                    'last_page_url' => $csrCompliance->url($csrCompliance->lastPage()),
                    'next_page_url' => $csrCompliance->nextPageUrl(),
                    'path' => $csrCompliance->path(),
                    'per_page' => $csrCompliance->perPage(),
                    'prev_page_url' => $csrCompliance->previousPageUrl(),
                    'to' => $csrCompliance->lastItem(),
                    'total' => $csrCompliance->total(),
                    'links' => $csrCompliance->linkCollection()->toArray()
                ]
            ]
        );
    }

    public function getProductionCsidStatus(Request $request): Response
    {
        $user = $request->attributes->get('user');
        $vat = $request->query->get('vat');

        $csrCompliance = DB::table('csr_production as c')
            ->leftJoin('csr_options as o', 'c.csr_options_id', '=', 'o.id')
            ->select([
                'c.request_id',
                'o.common_name',
                'o.organization_name',
                'o.organization_unit',
                'o.invoice_type',
                'o.egs_solution_name',
                'o.egs_model',
                'o.egs_serial_number',
                'c.created_at',
            ])
            ->where('c.vat_id', $vat)
            ->where('c.user_id', $user->id)
            ->where('o.user_id', $user->id)
            ->whereNull('c.deleted_at')
            ->orderBy('c.created_at', 'desc')
            ->paginate();

        $now = new DateTime();
        $nowMinus2Years = $now->sub(new DateInterval('P2Y'));

        foreach ($csrCompliance->items() as $item) {
            $createdAt = new DateTime($item->created_at);
            $item->is_expired = $createdAt < $nowMinus2Years;
        }

        return ApiResponse::success(
            array_values($csrCompliance->items()),
            201,
            [
                'pagination' => [
                    'current_page' => $csrCompliance->currentPage(),
                    'first_page_url' => $csrCompliance->url(1),
                    'from' => $csrCompliance->firstItem(),
                    'last_page' => $csrCompliance->lastPage(),
                    'last_page_url' => $csrCompliance->url($csrCompliance->lastPage()),
                    'next_page_url' => $csrCompliance->nextPageUrl(),
                    'path' => $csrCompliance->path(),
                    'per_page' => $csrCompliance->perPage(),
                    'prev_page_url' => $csrCompliance->previousPageUrl(),
                    'to' => $csrCompliance->lastItem(),
                    'total' => $csrCompliance->total(),
                    'links' => $csrCompliance->linkCollection()->toArray()
                ]
            ]
        );
    }
}
