<?php

namespace App\Helpers;

class Json
{
    public static function encode($value): string
    {
        return json_encode($value);
    }

    public static function decode(string $value)
    {
        return json_decode($value, true);
    }
}