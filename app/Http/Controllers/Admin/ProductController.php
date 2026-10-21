<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()->with(['category', 'images', 'inventoryLots'])->withTrashed()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', '%'.$request->string('q').'%')->orWhere('sku', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->latest()->paginate(15)->withQueryString();
        return view('admin.products.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }

    public function create() { return view('admin.products.form', $this->formData(new Product)); }
    public function edit(Product $product) { $product->load('images'); return view('admin.products.form', $this->formData($product)); }

    public function store(ProductRequest $request)
    {
        $product = DB::transaction(function () use ($request) {
            $data = $this->normalized($request);
            $product = Product::create($data);
            $this->saveImage($request, $product);
            return $product;
        });
        return redirect()->route('admin.products.edit', $product)->with('success', 'Đã thêm sản phẩm.');
    }

    public function update(ProductRequest $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($this->normalized($request));
            $this->saveImage($request, $product);
        });
        return back()->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);
        $product->delete();
        return back()->with('success', 'Đã chuyển sản phẩm vào thùng rác.');
    }

    public function restore(int $product)
    {
        Product::withTrashed()->findOrFail($product)->restore();
        return back()->with('success', 'Đã khôi phục sản phẩm.');
    }

    private function formData(Product $product): array
    {
        return ['product' => $product, 'categories' => Category::where('is_active', true)->orderBy('name')->get(), 'units' => DB::table('units')->orderBy('name')->get()];
    }
    private function normalized(ProductRequest $request): array
    {
        $data = $request->safe()->except('image');
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        return $data;
    }
    private function saveImage(ProductRequest $request, Product $product): void
    {
        if (! $request->hasFile('image')) return;
        $old = $product->images()->where('is_primary', true)->first();
        if ($old) { Storage::disk('public')->delete($old->path); $old->delete(); }
        $product->images()->create(['path' => $request->file('image')->store('products', 'public'), 'alt_text' => $product->name, 'is_primary' => true]);
    }
}
