<?php

namespace App\Http\Controllers;

use App\Models\Brand;
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

    public const PRICE_RANGES = [
        'duoi-50k' => ['label' => 'Dưới 50.000đ', 'min' => null, 'max' => 50000],
        '50-100k' => ['label' => '50.000đ – 100.000đ', 'min' => 50000, 'max' => 100000],
        '100-200k' => ['label' => '100.000đ – 200.000đ', 'min' => 100000, 'max' => 200000],
        'tren-200k' => ['label' => 'Trên 200.000đ', 'min' => 200000, 'max' => null],
    ];

    public function products(Request $request)
    {
        $filters = $this->shopFilters($request);
        [$priceSql, $priceBindings] = Product::effectivePriceSql();

        $base = fn (array $except = []) => Product::query()->where('is_active', true)
            ->when($filters['q'] !== '', fn ($query) => $query->where('name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['category'] && ! in_array('category', $except), fn ($query) => $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category'])))
            ->when($filters['brand'] && ! in_array('brand', $except), fn ($query) => $query->whereHas('brand', fn ($q) => $q->where('slug', $filters['brand'])))
            ->when($filters['min'] !== null, fn ($query) => $query->whereRaw("$priceSql >= ?", [...$priceBindings, $filters['min']]))
            ->when($filters['max'] !== null, fn ($query) => $query->whereRaw("$priceSql <= ?", [...$priceBindings, $filters['max']]))
            ->when($filters['sale'], fn ($query) => $query->whereRaw("$priceSql < selling_price", $priceBindings));

        $products = $base()->with(['category', 'brand', 'inventoryLots', 'images'])
            ->when($filters['sort'] === 'price_asc', fn ($query) => $query->orderByRaw("$priceSql asc", $priceBindings))
            ->when($filters['sort'] === 'price_desc', fn ($query) => $query->orderByRaw("$priceSql desc", $priceBindings))
            ->when($filters['sort'] === '', fn ($query) => $query->latest())
            ->paginate(12)->withQueryString();

        // Số lượng bên cạnh mỗi lựa chọn tính theo các bộ lọc còn lại.
        $categoryCounts = $base(['category'])->selectRaw('category_id, COUNT(*) as total')->groupBy('category_id')->pluck('total', 'category_id');
        $brandCounts = $base(['brand'])->whereNotNull('brand_id')->selectRaw('brand_id, COUNT(*) as total')->groupBy('brand_id')->pluck('total', 'brand_id');

        return view('store.products', [
            'products' => $products,
            'filters' => $filters,
            'priceRanges' => self::PRICE_RANGES,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get()
                ->each(fn ($category) => $category->filter_count = (int) ($categoryCounts[$category->id] ?? 0)),
            'brands' => Brand::query()->where('is_active', true)->whereHas('products', fn ($q) => $q->where('is_active', true))->orderBy('name')->get()
                ->each(fn ($brand) => $brand->filter_count = (int) ($brandCounts[$brand->id] ?? 0)),
        ]);
    }

    private function shopFilters(Request $request): array
    {
        $amount = fn (string $key) => is_numeric($request->query($key)) && $request->query($key) >= 0 ? (int) $request->query($key) : null;
        $range = self::PRICE_RANGES[$request->query('price')] ?? null;
        $min = $amount('min_price') ?? $range['min'] ?? null;
        $max = $amount('max_price') ?? $range['max'] ?? null;
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'category' => (string) $request->query('category', ''),
            'brand' => (string) $request->query('brand', ''),
            'price' => $range ? (string) $request->query('price') : '',
            'min' => $min,
            'max' => $max,
            'sale' => $request->boolean('sale'),
            'sort' => in_array($request->query('sort'), ['price_asc', 'price_desc'], true) ? $request->query('sort') : '',
        ];
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
