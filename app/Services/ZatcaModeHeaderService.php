<?php

namespace App\Services;

use App\Exceptions\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use ZATCA\Mode;

class ZatcaModeHeaderService
{
    public static function checkZatcaModeHeader(Request $request): string
    {
        $zatcaMode = $request->headers->get('Zatca-Mode');

        if (empty($zatcaMode)) {
            throw new BadRequestException(['Zatca-Mode' => 'Missing required header: Zatca-Mode']);
        }

        self::mapMode($zatcaMode);

        return $zatcaMode;
    }

    public static function mapMode(string $mode): Mode
    {
        $mapping = self::availableModes();

        if (!array_key_exists(strtolower($mode), $mapping)) {
            throw new BadRequestException([
                'Zatca-Mode' => 'Invalid Zatca-Mode header value. Allowed values: ' . implode(', ', array_keys($mapping))
            ]);
        }

        return $mapping[$mode];
    }

    public static function availableModes(): array
    {
        return [
            'simulation' => Mode::Sim,
            'developer-portal' => Mode::Dev,
            'core' => Mode::Pro,
        ];
    }
}
