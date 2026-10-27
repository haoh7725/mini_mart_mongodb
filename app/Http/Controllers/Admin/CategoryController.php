<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()->withCount('products')->withTrashed()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('sort_order')->paginate(12)->withQueryString();
        return view('admin.categories.index', compact('categories'));
    }
    public function create() { return view('admin.categories.form', ['category' => new Category]); }
    public function store(CategoryRequest $request)
    {
        $data = $request->safe()->except(['image_file', 'remove_image']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $category = Category::create($data);
        $this->saveImage($request, $category);
        return redirect()->route('admin.categories.index')->with('success', 'Đã thêm danh mục.');
    }
    public function edit(Category $category) { return view('admin.categories.form', compact('category')); }
    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->safe()->except(['image_file', 'remove_image']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $category->update($data);
        $this->saveImage($request, $category);
        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }
    public function destroy(Category $category)
    {
        abort_if($category->products()->where('is_active', true)->exists(), 422, 'Hãy ngừng bán sản phẩm trong danh mục trước khi xóa.');
        $category->delete();
        return back()->with('success', 'Đã chuyển danh mục vào thùng rác.');
    }
    public function restore(int $category)
    {
        Category::withTrashed()->findOrFail($category)->restore();
        return back()->with('success', 'Đã khôi phục danh mục.');
    }

    private function saveImage(CategoryRequest $request, Category $category): void
    {
        if ($request->boolean('remove_image') && $category->image) {
            Storage::disk(config('filesystems.media'))->delete($category->image);
            $category->update(['image' => null]);
        }

        if (! $request->hasFile('image_file')) return;

        if ($category->image) {
            Storage::disk(config('filesystems.media'))->delete($category->image);
        }

        $category->update([
            'image' => $request->file('image_file')->store('categories', config('filesystems.media')),
        ]);
    }
}
