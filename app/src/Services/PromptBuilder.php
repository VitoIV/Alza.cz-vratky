<?php

namespace App\Services;

use App\Repositories\TaxonomyRepository;

class PromptBuilder
{
    private array $teams;
    private array $categories;
    private array $rootCauses;

    public function __construct()
    {
        $taxonomy = new TaxonomyRepository();
        $this->teams = $taxonomy->allTeams();
        $this->categories = $taxonomy->categoriesByTeam();
        $this->rootCauses = $taxonomy->rootCausesByCategory();
    }

    public function taxonomyIndex(): array
    {
        $teams = [];
        foreach ($this->teams as $team) {
            $teams[$team['key']] = $team;
        }

        $categories = [];
        foreach ($this->categories as $teamId => $items) {
            foreach ($items as $item) {
                $categories[$item['key']] = $item;
            }
        }

        $rootCauses = [];
        foreach ($this->rootCauses as $categoryId => $items) {
            foreach ($items as $item) {
                $rootCauses[$item['key']] = $item;
            }
        }

        return [
            'teams' => $teams,
            'categories' => $categories,
            'root_causes' => $rootCauses,
        ];
    }

    public function systemPrompt(): string
    {
        $lines = [];
        $lines[] = 'You are a classification assistant for return reasons from an e-commerce company.';
        $lines[] = 'Analyse each customer statement and map it to the defined taxonomy. Always return valid JSON following the schema:';
        $lines[] = '{';
        $lines[] = '  "actionable_flag": "ANO" | "NE",';
        $lines[] = '  "team_key": string, -- one of the existing team keys';
        $lines[] = '  "category_key": string | null, -- must belong to the selected team';
        $lines[] = '  "root_cause_keys": string[], -- zero, one or two keys, each must belong to the selected category';
        $lines[] = '  "recommended_action": string | null, -- fill only when actionable_flag is "ANO"';
        $lines[] = '  "needs_review": boolean, -- true when you are not confident or taxonomy does not fit';
        $lines[] = '  "proposals": [ {';
        $lines[] = '      "type": "category" | "root_cause",';
        $lines[] = '      "suggested_key": string, -- machine readable proposal id (uppercase with underscores)';
        $lines[] = '      "name": string,';
        $lines[] = '      "description": string,';
        $lines[] = '      "prompt_definition": string,';
        $lines[] = '      "team_key": string,';
        $lines[] = '      "category_key": string | null';
        $lines[] = '    } ... ]';
        $lines[] = '}';
        $lines[] = 'Use Czech language for recommended_action when possible. Keep recommended_action short (max two sentences).';
        $lines[] = 'If the text clearly matches a glossary phrase such as "Nevhodný dárek" or "Omylem objednáno", set actionable_flag="NE" and team_key=CUSTOMER.';
        $lines[] = 'Never invent new teams. You may propose new categories or root causes only inside the proposals array.';
        $lines[] = 'If none of the existing categories/root causes fit, mark needs_review=true and provide a clear reason in recommended_action.';

        $lines[] = '\nTEAM DEFINITIONS:';
        foreach ($this->teams as $team) {
            $definition = trim(($team['description'] ?? '').' '.($team['prompt_definition'] ?? ''));
            $lines[] = sprintf('- %s (%s): %s', $team['key'], $team['name'], $definition ?: '');
        }

        $lines[] = '\nCATEGORY DEFINITIONS:';
        foreach ($this->categories as $teamId => $items) {
            $teamKey = $this->findTeamKey($teamId);
            foreach ($items as $item) {
                if (!$item['active']) {
                    continue;
                }
                $definition = trim(($item['description'] ?? '').' '.($item['prompt_definition'] ?? ''));
                $lines[] = sprintf('- %s (team %s): %s', $item['key'], $teamKey, $definition ?: '');
            }
        }

        $lines[] = '\nROOT CAUSE DEFINITIONS:';
        foreach ($this->rootCauses as $categoryId => $items) {
            $categoryKey = $this->findCategoryKey($categoryId);
            foreach ($items as $item) {
                if (!$item['active']) {
                    continue;
                }
                $definition = trim(($item['description'] ?? '').' '.($item['prompt_definition'] ?? ''));
                $lines[] = sprintf('- %s (category %s): %s', $item['key'], $categoryKey, $definition ?: '');
            }
        }

        return implode("\n", $lines);
    }

    public function userPrompt(array $record): string
    {
        $lines = [];
        $lines[] = 'Klasifikuj následující záznam:';
        $lines[] = 'Batch record ID: '.$record['id'];
        $lines[] = 'RMA: '.($record['rma'] ?? '');
        $lines[] = 'Produkt: '.($record['product_name'] ?? '');
        $lines[] = 'Kód produktu: '.($record['product_code'] ?? '');
        $lines[] = 'SEO prefix: '.($record['seo_prefix'] ?? '');
        $lines[] = 'Jazyk odhadu: '.($record['language'] ?? 'unknown');
        $lines[] = 'Text zákazníka: <<<'.$record['issue_text'].'>>>';
        $lines[] = 'Pokud je text prázdný, vrať actionable_flag="NE" a needs_review=true.';
        return implode("\n", $lines);
    }

    private function findTeamKey(int $teamId): string
    {
        foreach ($this->teams as $team) {
            if ((int) $team['id'] === $teamId) {
                return $team['key'];
            }
        }
        return 'UNKNOWN';
    }

    private function findCategoryKey(int $categoryId): string
    {
        foreach ($this->categories as $items) {
            foreach ($items as $item) {
                if ((int) $item['id'] === $categoryId) {
                    return $item['key'];
                }
            }
        }
        return 'UNKNOWN';
    }
}
