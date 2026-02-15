<?php

namespace ZATCA;

use DateTime;

class TagsSchema
{
    public static function call(array $data): SchemaResult
    {
        $errors = [];

        // required string fields
        $requiredStrings = [
            'seller_name',
            'vat_registration_number',
            'invoice_total',
            'vat_total',
        ];
        foreach ($requiredStrings as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || !is_string($data[$field])) {
                $errors[$field][] = 'must be a non-empty string';
            }
        }

        // timestamp: required, must be string or valid datetime
        if (!isset($data['timestamp']) || $data['timestamp'] === '') {
            $errors['timestamp'][] = 'is required';
        } else {
            $ts = $data['timestamp'];
            if (!is_string($ts)) {
                $errors['timestamp'][] = 'must be a string or date-time';
            }
        }

        // optional string fields
        $optionalStrings = [
            'xml_invoice_hash',
            'ecdsa_signature',
            'ecdsa_public_key',
            'ecdsa_stamp_signature',
        ];
        foreach ($optionalStrings as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '' && !is_string($data[$field])) {
                $errors[$field][] = 'must be a string';
            }
        }

        return new SchemaResult($errors);
    }
}

class SchemaResult
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function failure(): bool
    {
        return !empty($this->errors);
    }

    public function errors(bool $full = false): SchemaErrors
    {
        return new SchemaErrors($this->errors);
    }
}

class SchemaErrors
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function toArray(): array
    {
        return $this->errors;
    }
}
