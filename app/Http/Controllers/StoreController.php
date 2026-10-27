<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function home()
    {
        return view('store.home', [
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'featured' => Product::query()->with(['category', 'inventoryLots', 'images'])->where('is_active', true)->where('is_featured', true)->latest()->take(8)->get(),
            'heroSlides' => $this->heroSlides(),
        ]);
    }

    // Mỗi banner chọn sản phẩm còn hàng đang giảm sâu nhất của danh mục để đặt ngay.
    private function heroSlides()
    {
        $slides = [
            ['category' => 'rau-cu-qua', 'theme' => 'green', 'eyebrow' => 'Rau củ quả · Hái sáng nay', 'title' => 'Rau củ tươi rói,', 'highlight' => 'giảm đến :discount%', 'fallback' => 'giá tốt mỗi ngày', 'text' => 'Nhập mới mỗi sáng từ nông trại VietGAP, giao tận cửa trong 2 giờ để bữa cơm nhà luôn tươi ngon.', 'link' => 'Xem rau củ'],
            ['category' => 'thit-hai-san', 'theme' => 'ocean', 'eyebrow' => 'Thịt & Hải sản · Tươi sống', 'title' => 'Hải sản tươi ngon,', 'highlight' => 'ưu đãi :discount%', 'fallback' => 'chuẩn vị biển', 'text' => 'Cá hồi, thịt sạch được cấp đông và bảo quản lạnh đúng chuẩn, đóng gói gọn gàng từng phần ăn.', 'link' => 'Xem thịt & hải sản'],
            ['category' => 'sua-trung', 'theme' => 'sun', 'eyebrow' => 'Sữa & Trứng · Bữa sáng đủ chất', 'title' => 'Bữa sáng đủ chất,', 'highlight' => 'tiết kiệm :discount%', 'fallback' => 'cho cả nhà', 'text' => 'Sữa tươi, trứng gà thả vườn giàu dinh dưỡng, đặt tối nay sáng mai đã có trên bàn ăn.', 'link' => 'Xem sữa & trứng'],
            ['category' => 'do-kho', 'theme' => 'earth', 'eyebrow' => 'Đồ khô · Tích trữ thông minh', 'title' => 'Gạo ngon, đồ khô', 'highlight' => 'giảm :discount% hôm nay', 'fallback' => 'đầy đủ cho bếp', 'text' => 'Gạo ST25, gia vị và đồ khô chính hãng. Đơn từ 300.000đ được miễn phí giao hàng.', 'link' => 'Xem đồ khô'],
        ];

        $products = Product::query()->with(['category', 'inventoryLots', 'images'])->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->whereIn('slug', array_column($slides, 'category')))->get()
            ->filter(fn ($product) => $product->stock > 0);
        $discount = fn ($product) => (float) $product->selling_price > 0
            ? (int) round((1 - $product->current_price / (float) $product->selling_price) * 100) : 0;

        return collect($slides)->map(function (array $slide) use ($products, $discount) {
            $product = $products->where('category.slug', $slide['category'])->sortByDesc($discount)->first();
            if (! $product) return null;
            $percent = $discount($product);

            return array_merge($slide, [
                'product' => $product,
                'discount' => $percent,
                'highlight' => $percent > 0 ? str_replace(':discount', $percent, $slide['highlight']) : $slide['fallback'],
            ]);
        })->filter()->values();
    }

    public function products(Request $request)
    {
        $products = Product::query()->with(['category', 'inventoryLots', 'images'])->where('is_active', true)
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category'))))
            ->when($request->get('sort') === 'price_asc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, selling_price) asc'))
            ->when($request->get('sort') === 'price_desc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, selling_price) desc'))
            ->when(!$request->filled('sort'), fn ($query) => $query->latest())
            ->paginate(12)->withQueryString();

        return view('store.products', [
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);
        $product->load(['category', 'inventoryLots', 'images']);
        $reviews = $product->reviews()->with('user')->where('status', 'approved')->latest()->paginate(8);
        $ratingSummary = ['average' => round((float) $product->reviews()->where('status', 'approved')->avg('rating'), 1), 'count' => $product->reviews()->where('status', 'approved')->count()];
        $related = Product::query()->with(['inventoryLots', 'images'])->where('is_active', true)->where('category_id', $product->category_id)->whereKeyNot($product->id)->take(4)->get();

        return view('store.show', compact('product', 'related', 'reviews', 'ratingSummary'));
    }
}
