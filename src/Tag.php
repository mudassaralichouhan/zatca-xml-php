<?php

namespace ZATCA;

class Tag
{
    private const TAG_IDS = [
        'seller_name'               => 1,
        'vat_registration_number'   => 2,
        'timestamp'                 => 3,
        'invoice_total'             => 4,
        'vat_total'                 => 5,
        'xml_invoice_hash'          => 6,
        'ecdsa_signature'           => 7,
        'ecdsa_public_key'          => 8,
        'ecdsa_stamp_signature'     => 9,
    ];

    private const PHASE_1_TAGS = [
        'seller_name',
        'vat_registration_number',
        'timestamp',
        'invoice_total',
        'vat_total',
    ];

    public int $id;
    public string $key;
    public string $value;

    public function __construct(string $key, $value)
    {
        $this->id    = self::TAG_IDS[$key];
        $this->key   = $key;
        $this->value = (string) $value;
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'key'   => $this->key,
            'value' => $this->value,
        ];
    }

    public function shouldBeUtf8Encoded(): bool
    {
        return in_array($this->key, self::PHASE_1_TAGS, true);
    }

    public function toTlv(): string
    {
        // ID byte + length byte + value
        return chr($this->id) . chr(strlen($this->value)) . $this->value;
    }
}
