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
        ]);
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
        $related = Product::query()->with(['inventoryLots', 'images'])->where('is_active', true)->where('category_id', $product->category_id)->whereKeyNot($product->id)->take(4)->get();

        return view('store.show', compact('product', 'related'));
    }
}
