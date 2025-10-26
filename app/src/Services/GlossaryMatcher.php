<?php

namespace App\Services;

use App\Repositories\GlossaryRepository;
use App\Support\TextNormalizer;

class GlossaryMatcher
{
    private array $entries;

    public function __construct(?array $entries = null)
    {
        if ($entries === null) {
            $entries = (new GlossaryRepository())->activeEntries();
        }
        $this->entries = $entries;
    }

    public function match(string $text, string $language): ?array
    {
        $canonical = TextNormalizer::canonical($text);
        if ($canonical === '') {
            return null;
        }

        foreach ($this->entries as $entry) {
            if (!$entry['active']) {
                continue;
            }
            if ($entry['language'] !== 'unknown' && $entry['language'] !== $language) {
                continue;
            }
            $score = $this->similarity($canonical, $entry['normalized_phrase']);
            if ($score >= (float) $entry['threshold']) {
                return [
                    'team_id' => $entry['team_id'],
                    'category_id' => $entry['category_id'],
                    'root_cause_id' => $entry['root_cause_id'],
                    'actionable_flag' => (bool) $entry['actionable_flag'],
                    'notes' => $entry['notes'],
                    'phrase' => $entry['phrase'],
                ];
            }
        }

        return null;
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        similar_text($a, $b, $percent);
        return $percent / 100.0;
    }
}
