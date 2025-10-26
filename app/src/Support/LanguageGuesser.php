<?php

namespace App\Support;

class LanguageGuesser
{
    private const LANGUAGE_KEYWORDS = [
        'cs' => ['ě', 'š', 'č', 'ř', 'ž', 'ů', 'á', 'í', 'ý', 'é', 'dárek', 'vratka'],
        'sk' => ['ľ', 'š', 'ť', 'ž', 'ý', 'á', 'é', 'ú', 'návrat', 'darček'],
        'hu' => ['á', 'é', 'í', 'ó', 'ö', 'ő', 'ú', 'ü', 'ű', 'nem', 'vissza'],
        'de' => ['ä', 'ö', 'ü', 'ß', 'nicht', 'zurück'],
        'en' => ['return', 'refund', 'wrong', 'size', 'late'],
    ];

    public static function guess(string $text): ?string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $scores = [];
        foreach (self::LANGUAGE_KEYWORDS as $lang => $keywords) {
            $scores[$lang] = 0;
            foreach ($keywords as $word) {
                if (str_contains($text, $word)) {
                    $scores[$lang] += 1;
                }
            }
        }
        arsort($scores);
        $top = array_key_first($scores);
        if ($top === null || $scores[$top] === 0) {
            return null;
        }
        return $top;
    }
}
