<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Toàn quyền hệ thống'],
            ['name' => 'Quản lý', 'slug' => 'manager', 'description' => 'Quản lý vận hành cửa hàng'],
            ['name' => 'Nhân viên', 'slug' => 'staff', 'description' => 'Xử lý sản phẩm, kho và đơn hàng'],
            ['name' => 'Khách hàng', 'slug' => 'customer', 'description' => 'Mua hàng trên website'],
            ['name' => 'Shipper', 'slug' => 'shipper', 'description' => 'Nhận và giao đơn hàng của cửa hàng'],
        ])->mapWithKeys(fn (array $role) => [
            $role['slug'] => Role::query()->updateOrCreate(['slug' => $role['slug']], $role),
        ]);

        foreach ([
            ['Quản trị hệ thống', 'admin@minimart.test', 'super-admin'],
            ['Quản lý cửa hàng', 'manager@minimart.test', 'manager'],
            ['Nhân viên bán hàng', 'staff@minimart.test', 'staff'],
            ['Khách hàng mẫu', 'customer@minimart.test', 'customer'],
            ['Shipper Minh Tuấn', 'shipper@minimart.test', 'shipper'],
        ] as [$name, $email, $role]) {
            User::query()->updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'Password@123',
                'role_id' => $roles[$role]->id,
                'email_verified_at' => now(),
            ]);
        }
        // Khách thấy số này để gọi shipper khi đơn đang giao.
        User::query()->where('email', 'shipper@minimart.test')->update(['phone' => '0909123456']);

        $categories = collect([
            ['name' => 'Rau củ quả', 'slug' => 'rau-cu-qua', 'description' => 'Tươi mới từ nông trại', 'sort_order' => 1],
            ['name' => 'Thịt & Hải sản', 'slug' => 'thit-hai-san', 'description' => 'Kiểm soát chất lượng nghiêm ngặt', 'sort_order' => 2],
            ['name' => 'Sữa & Trứng', 'slug' => 'sua-trung', 'description' => 'Dinh dưỡng mỗi ngày', 'sort_order' => 3],
            ['name' => 'Đồ uống', 'slug' => 'do-uong', 'description' => 'Tươi mát mọi khoảnh khắc', 'sort_order' => 4],
            ['name' => 'Đồ khô', 'slug' => 'do-kho', 'description' => 'Thiết yếu cho căn bếp', 'sort_order' => 5],
            ['name' => 'Chăm sóc nhà cửa', 'slug' => 'cham-soc-nha-cua', 'description' => 'Nhà sạch, sống khỏe', 'sort_order' => 6],
        ])->mapWithKeys(function ($category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            );

            return [$category['slug'] => DB::table('categories')->where('slug', $category['slug'])->value('id')];
        });

        DB::table('units')->updateOrInsert(['symbol' => 'sp'], ['name' => 'Sản phẩm', 'created_at' => now(), 'updated_at' => now()]);
        $unitId = DB::table('units')->where('symbol', 'sp')->value('id');
        DB::table('warehouses')->updateOrInsert(['code' => 'KHO-HCM'], ['name' => 'Kho trung tâm', 'address' => 'TP. Hồ Chí Minh', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $warehouseId = DB::table('warehouses')->where('code', 'KHO-HCM')->value('id');
        $products = [
            ['Rau cải ngọt VietGAP 500g', 'rau-cai-ngot-vietgap', 'RAU001', 'rau-cu-qua', 28000, null, 'Rau xanh giòn ngọt, thu hoạch trong ngày.'],
            ['Cà chua beef Đà Lạt 500g', 'ca-chua-beef-da-lat', 'RAU002', 'rau-cu-qua', 35000, 29000, 'Cà chua mọng nước, phù hợp salad và nấu ăn.'],
            ['Bơ sáp Tây Nguyên 1kg', 'bo-sap-tay-nguyen', 'RAU003', 'rau-cu-qua', 89000, 79000, 'Bơ dẻo béo tự nhiên, tuyển chọn trái vừa chín.'],
            ['Ức gà phi lê tươi 500g', 'uc-ga-phi-le-tuoi', 'THIT001', 'thit-hai-san', 72000, null, 'Thịt gà tươi, sơ chế sạch, giàu protein.'],
            ['Cá hồi Na Uy phi lê 300g', 'ca-hoi-na-uy-phi-le', 'THIT002', 'thit-hai-san', 189000, 169000, 'Cá hồi nhập khẩu, béo thơm và giàu Omega-3.'],
            ['Thịt heo ba rọi 500g', 'thit-heo-ba-roi', 'THIT003', 'thit-hai-san', 99000, null, 'Ba rọi heo tươi với tỷ lệ nạc mỡ cân bằng.'],
            ['Sữa tươi không đường 1L', 'sua-tuoi-khong-duong', 'SUA001', 'sua-trung', 39000, 35000, 'Sữa tươi thanh trùng giàu canxi, không đường.'],
            ['Trứng gà thả vườn hộp 10', 'trung-ga-tha-vuon', 'SUA002', 'sua-trung', 42000, null, 'Trứng gà tươi, lòng đỏ đậm màu tự nhiên.'],
            ['Nước cam nguyên chất 1L', 'nuoc-cam-nguyen-chat', 'NUOC001', 'do-uong', 65000, 55000, 'Nước cam ép nguyên chất, không chất bảo quản.'],
            ['Cà phê rang xay nguyên chất 500g', 'ca-phe-rang-xay', 'NUOC002', 'do-uong', 145000, null, 'Hương thơm đậm, vị cân bằng kiểu Việt.'],
            ['Gạo ST25 túi 5kg', 'gao-st25-5kg', 'KHO001', 'do-kho', 185000, 169000, 'Gạo thơm thượng hạng, hạt dài, cơm dẻo.'],
            ['Nước giặt thiên nhiên 3L', 'nuoc-giat-thien-nhien', 'NHA001', 'cham-soc-nha-cua', 159000, 139000, 'Sạch dịu nhẹ, hương thơm tươi mát.'],
        ];
        foreach ($products as $index => [$name,$slug,$sku,$category,$price,$sale,$description]) {
            DB::table('products')->updateOrInsert(['sku' => $sku], [
                'category_id' => $categories[$category], 'unit_id' => $unitId, 'name' => $name, 'slug' => $slug,
                'short_description' => $description, 'description' => $description,
                'cost_price' => $price * .7, 'selling_price' => $price, 'sale_price' => $sale,
                'is_featured' => $index < 8, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $productId = DB::table('products')->where('sku', $sku)->value('id');
            DB::table('inventory_lots')->updateOrInsert(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId, 'product_variant_id' => null],
                ['quantity' => 50 + $index * 3, 'unit_cost' => $price * .7, 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $this->call(BrandSeeder::class);
        $this->call(CouponSeeder::class);

        if (! app()->environment('testing')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
