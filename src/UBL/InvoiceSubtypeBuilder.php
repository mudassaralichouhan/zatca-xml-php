<?php

namespace ZATCA\UBL;

class InvoiceSubtypeBuilder
{
    public static function build(
        bool $simplified,
        bool $thirdParty,
        bool $nominal,
        bool $exports,
        bool $summary,
        bool $selfBilled
    ): string {
        $subtypePrefix = $simplified ? '02' : '01';

        $values = [
            $thirdParty,
            $nominal,
            $exports,
            $summary,
            $selfBilled
        ];

        $binaryValues = array_map(fn (bool $v) => $v ? '1' : '0', $values);

        return $subtypePrefix . implode('', $binaryValues);
    }
}
