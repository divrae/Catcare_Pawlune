<?php

namespace App\Support;

use Carbon\Carbon;

class Fmt
{
    public static function rp($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    public static function date($date, string $format = 'd M Y'): string
    {
        if (! $date) {
            return '-';
        }
        return Carbon::parse($date)->locale('id')->translatedFormat($format);
    }

    /** Normalisasi nomor HP Indonesia -> 08xxxxxxxx */
    public static function phone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        } elseif (! str_starts_with($digits, '0')) {
            $digits = '0' . $digits;
        }
        return $digits;
    }
}
