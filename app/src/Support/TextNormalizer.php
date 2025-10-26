<?php

namespace App\Support;

class TextNormalizer
{
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[\p{P}\p{S}]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', trim($text));
        return $text;
    }

    public static function canonical(string $text): string
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return '';
        }
        $tokens = preg_split('/\s+/u', $normalized);
        sort($tokens, SORT_STRING | SORT_FLAG_CASE);
        return implode(' ', $tokens);
    }

    public static function hash(string $text): string
    {
        return sha1(self::normalize($text));
    }
}
