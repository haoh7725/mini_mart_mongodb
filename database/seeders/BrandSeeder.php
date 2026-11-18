<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    // Thương hiệu => SKU sản phẩm mẫu. Chỉ gán cho sản phẩm chưa có thương hiệu.
    private const BRANDS = [
        'VietGAP Farm' => ['RAU001', 'RAU002'],
        'Nông sản Tây Nguyên' => ['RAU003', 'NUOC002'],
        'MiniMart Fresh' => ['THIT001', 'THIT003', 'SUA002', 'NUOC001'],
        'Ocean Fresh' => ['THIT002'],
        'Dalat Milk' => ['SUA001'],
        'Neptune' => ['KHO001'],
        'Green Home' => ['NHA001'],
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $name => $skus) {
            $slug = Str::slug($name);
            DB::table('brands')->updateOrInsert(['slug' => $slug], ['name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $brandId = (string) DB::table('brands')->where('slug', $slug)->value('id');
            DB::table('products')->whereIn('sku', $skus)->whereNull('brand_id')->update(['brand_id' => $brandId]);
        }
    }
}
