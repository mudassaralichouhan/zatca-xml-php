<?php

namespace App\Services;

use App\Config\Paths;
use Illuminate\Database\Capsule\Manager as DB;
use App\Exceptions\BadRequestException;
use App\Exceptions\ValidationException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator as JsonValidator;

class ValidatorService
{
    public static function load(string $body, string $schema)
    {
        $data = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException(['Invalid JSON: ' . json_last_error_msg()]);
        }

        $schema = json_decode(
            file_get_contents(Paths::SCHEMAS . $schema)
        );

        $validator = new JsonValidator();
        $validator->validate(
            $data,
            $schema,
            Constraint::CHECK_MODE_VALIDATE_SCHEMA
        );

        if (!$validator->isValid()) {
            $issues = array_map(
                fn (array $err) => [
                    'path' => $err['pointer'] ?: '/',
                    'message' => $err['message'],
                ],
                $validator->getErrors()
            );

            throw new ValidationException($issues);
        }

        return $data;
    }

    public static function isExist(string $invoiceId, string $userId): bool
    {
        $exists = DB::table('invoices')
            ->where([
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
            ])
            ->exists();

        if ($exists) {
            throw new ValidationException(['invoice_id' => "Invoice already exists {$invoiceId}"]);
        }

        return false;
    }
}
