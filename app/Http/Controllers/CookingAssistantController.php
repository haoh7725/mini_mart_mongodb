<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CookingAssistantService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use RuntimeException;

class CookingAssistantController extends Controller
{
    public function index(CookingAssistantService $assistant)
    {
        return view('store.cooking-assistant', ['configured' => $assistant->configured()]);
    }

    public function ask(Request $request, CookingAssistantService $assistant)
    {
        $data = $request->validate(['message' => ['required', 'string', 'min:3', 'max:500']]);
        $products = Product::with(['inventoryLots', 'activeRescueCampaign'])->where('is_active', true)->get()->filter(fn ($product) => $product->stock > 0);

        try {
            $result = $assistant->ask($data['message'], $products);
        } catch (RequestException $exception) {
            report($exception);
            $status = $exception->response->status();
            $apiMessage = (string) $exception->response->json('error.message', '');
            $message = match (true) {
                in_array($status, [401, 403], true) => 'Gemini API key không hợp lệ, bị giới hạn hoặc chưa có quyền sử dụng.',
                $status === 429 => 'Gemini đã vượt giới hạn lượt gọi hoặc quota. Vui lòng đợi rồi thử lại.',
                $status === 404 => 'Model Gemini đang cấu hình không khả dụng cho tài khoản này.',
                $status === 400 && str_contains(strtolower($apiMessage), 'api key') => 'Gemini API key không hợp lệ. Hãy kiểm tra lại key trong file .env.',
                $status === 400 => 'Yêu cầu gửi đến Gemini chưa hợp lệ. Hãy kiểm tra model đang cấu hình.',
                default => 'Gemini đang tạm thời không phản hồi. Vui lòng thử lại sau.',
            };

            return response()->json(['message' => $message], 502);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $matched = $products->whereIn('id', $result['product_ids'])->map(fn ($product) => [
            'id' => $product->id, 'name' => $product->name,
            'price' => number_format($product->current_price, 0, ',', '.').'đ',
            'image' => $product->primary_image_url,
        ])->values();

        return response()->json($result + ['products' => $matched]);
    }

    public function addProducts(Request $request)
    {
        $data = $request->validate(['product_ids' => ['required', 'array', 'max:20'], 'product_ids.*' => ['integer', 'distinct', 'exists:products,id']]);
        $products = Product::with('inventoryLots')->whereIn('id', $data['product_ids'])->where('is_active', true)->get();
        $cart = $request->session()->get('cart', []);
        $added = 0;
        foreach ($products as $product) {
            if ($product->stock < 1) {
                continue;
            }
            $cart[$product->id] = min((int) ($cart[$product->id] ?? 0) + 1, (int) $product->stock, 99);
            $added++;
        }
        $request->session()->put('cart', $cart);

        return response()->json(['message' => "Đã thêm {$added} nguyên liệu vào giỏ.", 'cart_count' => array_sum($cart)]);
    }
}
