<?php

use App\Support\Fmt;

if (! function_exists('rupiah')) {
    function rupiah($value): string
    {
        return Fmt::rp($value);
    }
}

if (! function_exists('tgl')) {
    function tgl($date, string $format = 'd M Y'): string
    {
        return Fmt::date($date, $format);
    }
}
