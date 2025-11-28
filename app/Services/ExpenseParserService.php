<?php

namespace App\Services;

use Anthropic\Laravel\Facades\Anthropic;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpenseParserService
{
    public function parse(string $input, int $userId): array
    {
        try {
            // Get user's categories for context
            $categories = Category::query()
                ->where('user_id', $userId)
                ->get(['id', 'name'])
                ->pluck('name', 'id')
                ->toArray();

            $categoryList = empty($categories)
                ? 'No categories exist yet.'
                : 'Available categories: '.implode(', ', $categories);

            $systemPrompt = $this->buildSystemPrompt($categoryList);

            $response = Anthropic::messages()->create([
                'model' => 'claude-sonnet-4-5-20250929',
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $input,
                    ],
                ],
            ]);

            $content = $response->content[0]->text ?? null;

            if (! $content) {
                throw new \Exception('No response from Claude API');
            }

            // Extract JSON from response (in case Claude adds explanation text)
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');

            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('Invalid JSON response from Claude');
            }

            $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode JSON: '.json_last_error_msg());
            }

            // Validate and normalize the response
            return $this->normalizeResponse($parsed, $input, $categories);
        } catch (\Exception $e) {
            Log::error('ExpenseParserService failed', [
                'input' => $input,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function buildSystemPrompt(string $categoryList): string
    {
        return <<<'PROMPT'
You are an expense parser. Your job is to extract transaction information from natural language input and return it as JSON.

Extract:
1. **amount**: The monetary value (positive number, no currency symbols)
2. **name**: Short description/merchant name (e.g., "Starbucks", "Weekly groceries")
3. **type**: Either "income" or "expense"
4. **category**: Suggest from the user's categories if applicable, or null if uncertain
5. **date**: ISO format (YYYY-MM-DD), parse relative dates like "yesterday", "last Friday", default to today
6. **confidence**: Float 0-1 indicating parsing confidence

Currency symbols to handle: £, $, €, and words like "quid", "pounds", "dollars", "euros"

Date parsing examples:
- "yesterday" = yesterday's date
- "last Friday" = most recent Friday
- "on the 15th" = 15th of current month
- no date mentioned = today

Type detection:
- "spent", "bought", "paid" = expense
- "earned", "got paid", "received" = income
- "saved", "transfer to savings" = expense with savings context

{$categoryList}

Return ONLY valid JSON in this exact format:
{
    "amount": 4.50,
    "name": "Starbucks coffee",
    "type": "expense",
    "category": "Food & Drink",
    "date": "2024-01-15",
    "confidence": 0.95
}

Rules:
- If no category matches well, use null
- Always include all fields
- confidence should reflect how certain you are about the parsing
- Return clean, parseable JSON only
PROMPT;
    }

    protected function normalizeResponse(array $parsed, string $rawInput, array $categories): array
    {
        // Find matching category ID if category name was suggested
        $categoryId = null;
        if (! empty($parsed['category'])) {
            $categoryId = array_search($parsed['category'], $categories, true);
            if ($categoryId === false) {
                $categoryId = null;
            }
        }

        return [
            'amount' => (float) ($parsed['amount'] ?? 0),
            'name' => $parsed['name'] ?? 'Unknown',
            'type' => in_array($parsed['type'] ?? '', ['income', 'expense'], true)
                ? $parsed['type']
                : 'expense',
            'category_id' => $categoryId,
            'category_name' => $parsed['category'] ?? null,
            'date' => $this->parseDate($parsed['date'] ?? null),
            'confidence' => (float) ($parsed['confidence'] ?? 0),
            'raw_input' => $rawInput,
        ];
    }

    protected function parseDate(?string $date): string
    {
        if (empty($date)) {
            return Carbon::today()->toDateString();
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return Carbon::today()->toDateString();
        }
    }
}
