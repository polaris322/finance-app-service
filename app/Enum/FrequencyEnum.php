<?php

namespace App\Enum;

enum FrequencyEnum: string {
    case SEMI_ANNUAL = '0';
    case QUART_ANNUAL = '1';
    case MONTHLY = '2';
    case ANNUAL = '3';
    case UNIQUE = '4';

    public static function getMonthlyDifference(FrequencyEnum $frequency): int
    {
        return match ($frequency) {
            FrequencyEnum::SEMI_ANNUAL => 6,
            FrequencyEnum::QUART_ANNUAL => 3,
            FrequencyEnum::MONTHLY => 1,
            FrequencyEnum::ANNUAL => 12,
            default => 0,
        };
    }
}
