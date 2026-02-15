<?php

namespace App\Controllers;

use App\Exceptions\BadRequestException;
use App\Services\CryptoService;
use App\Services\ValidatorService;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ApiResponse;

class ApiKeyController extends BaseController
{
    public function regenerate(Request $request): Response
    {
        $user = $request->attributes->get('user');

        $data_to_encrypt = json_encode(['id' => $user->id, 'zatca_mode' => $user->zatca_mode]);

        $encrypted_data = CryptoService::encrypt($data_to_encrypt);

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'api_key' => $encrypted_data,
                'expire_at' => date('Y-m-d H:i:s', strtotime('+30 day')),
            ]);

        return ApiResponse::success([
            'api_key' => $encrypted_data,
        ]);
    }

    public function all(Request $request): Response
    {
        $user = $request->attributes->get('user');

        $whitelisting = DB::table('whitelists')
            ->where('user_id', $user->id)
            ->paginate();

        return ApiResponse::success(
            array_values($whitelisting->items()), // Actual data in 'data' field as clean array
            200, // status
            [ // pagination metadata in 'meta' field
                'pagination' => [
                    'current_page' => $whitelisting->currentPage(),
                    'first_page_url' => $whitelisting->url(1),
                    'from' => $whitelisting->firstItem(),
                    'last_page' => $whitelisting->lastPage(),
                    'last_page_url' => $whitelisting->url($whitelisting->lastPage()),
                    'next_page_url' => $whitelisting->nextPageUrl(),
                    'path' => $whitelisting->path(),
                    'per_page' => $whitelisting->perPage(),
                    'prev_page_url' => $whitelisting->previousPageUrl(),
                    'to' => $whitelisting->lastItem(),
                    'total' => $whitelisting->total(),
                    'links' => $whitelisting->linkCollection()->toArray()
                ]
            ]
        );
    }

    public function createOrUpdate(Request $request): Response
    {
        $user = $request->attributes->get('user');

        $data = ValidatorService::load($request->getContent(), 'auth.api-key.json');

        DB::beginTransaction();

        foreach ($data as $payload) {
            DB::table('whitelists')
                ->updateOrInsert([
                    'user_id' => $user->id,
                    'type' => $payload->type,
                    'value' => $payload->value,
                ], [
                    'user_id' => $user->id,
                    'type' => $payload->type,
                    'value' => $payload->value,
                ]);
        }

        DB::commit();
        // DB::rollBack();

        $keys = DB::table('whitelists')
            ->select(['type', 'value', 'created_at'])
            ->where('user_id', $user->id)
            ->paginate();

        return ApiResponse::success(
            array_values($keys->items()),
            201,
            [
                'pagination' => [
                    'current_page' => $keys->currentPage(),
                    'first_page_url' => $keys->url(1),
                    'from' => $keys->firstItem(),
                    'last_page' => $keys->lastPage(),
                    'last_page_url' => $keys->url($keys->lastPage()),
                    'next_page_url' => $keys->nextPageUrl(),
                    'path' => $keys->path(),
                    'per_page' => $keys->perPage(),
                    'prev_page_url' => $keys->previousPageUrl(),
                    'to' => $keys->lastItem(),
                    'total' => $keys->total(),
                    'links' => $keys->linkCollection()->toArray()
                ]
            ]
        );
    }

    public function flush(Request $request): Response
    {
        $user = $request->attributes->get('user');

        DB::beginTransaction();

        DB::table('whitelists')
            ->where('user_id', $user->id)
            ->delete();

        DB::commit();
        // DB::rollBack();

        return ApiResponse::success([], 204);
    }

    public function destroy(Request $request): Response
    {
        $user = $request->attributes->get('user');
        $value = $request->query->get('value') ?? throw new BadRequestException(['value' => 'whitelisting value is required. in query parameters.']);

        DB::beginTransaction();

        DB::table('whitelists')
            ->where('user_id', $user->id)
            ->where('value', $value)
            ->delete();

        DB::commit();
        // DB::rollBack();

        return ApiResponse::success([], 204);
    }
}
