<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CookingAssistantService
{
    public function configured(): bool
    {
        return filled(config('services.gemini.api_key'));
    }

    public function ask(string $message, Collection $products): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Trợ lý chưa được cấu hình Gemini API key.');
        }

        $catalog = $products->map(fn ($product) => [
            'id' => $product->id, 'name' => $product->name,
            'price' => $product->current_price, 'stock' => $product->stock,
        ])->values()->all();

        $model = config('services.gemini.model', 'gemini-3.5-flash-lite');
        $response = Http::withHeaders(['x-goog-api-key' => config('services.gemini.api_key')])
            ->acceptJson()->timeout(45)->retry(1, 300)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'systemInstruction' => ['parts' => [['text' => 'Bạn là trợ lý nấu ăn tiếng Việt của MiniMart. Trả lời ngắn gọn, thân thiện. Đề xuất nguyên liệu và các bước nấu an toàn. Chỉ đưa product_ids có thật trong danh mục và phù hợp trực tiếp với món. Không đưa lời khuyên y tế. Nếu yêu cầu không liên quan nấu ăn, hãy lịch sự hướng người dùng về món ăn và nguyên liệu.']]],
                'contents' => [['role' => 'user', 'parts' => [['text' => "Yêu cầu của khách: {$message}\n\nDanh mục MiniMart hiện có:\n".json_encode($catalog, JSON_UNESCAPED_UNICODE)]]]],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 900,
                    'responseMimeType' => 'application/json',
                    'responseJsonSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'dish' => ['type' => 'string'],
                            'answer' => ['type' => 'string'],
                            'ingredients' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'steps' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'product_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        ],
                        'required' => ['dish', 'answer', 'ingredients', 'steps', 'product_ids'],
                    ],
                ],
            ])->throw()->json();

        $text = data_get($response, 'candidates.0.content.parts.0.text');
        $result = json_decode((string) $text, true);
        if (! is_array($result)) throw new RuntimeException('Không đọc được câu trả lời từ trợ lý.');

        $allowedIds = $products->pluck('id')->map(fn ($id) => (int) $id);
        $result['product_ids'] = collect($result['product_ids'] ?? [])->map(fn ($id) => (int) $id)->intersect($allowedIds)->unique()->values()->all();
        return $result;
    }
}
