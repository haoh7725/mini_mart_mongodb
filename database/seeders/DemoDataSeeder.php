<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\DocumentTransaction;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create('vi_VN');
        $customerRole = Role::query()->where('slug', 'customer')->firstOrFail();
        $products = Product::query()->where('is_active', true)->get();

        if ($products->isEmpty()) {
            $this->command?->warn('Chưa có sản phẩm nên không thể tạo đơn hàng mẫu.');

            return;
        }

        $customers = collect(range(1, 12))->map(function (int $number) use ($faker, $customerRole) {
            return User::query()->firstOrCreate(
                ['email' => sprintf('khachhang%02d@example.test', $number)],
                [
                    'name' => $faker->name(),
                    'password' => 'Password@123',
                    'role_id' => $customerRole->id,
                    'email_verified_at' => now(),
                ],
            );
        });

        $statuses = ['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled'];

        foreach (range(1, 30) as $number) {
            $code = sprintf('DEMO%05d', $number);

            if (DB::table('orders')->where('code', $code)->exists()) {
                continue;
            }

            $customer = $customers->random();
            $status = $faker->randomElement($statuses);
            $createdAt = now()->subDays($faker->numberBetween(0, 60))->subMinutes($faker->numberBetween(0, 900));
            $selectedProducts = $products->random($faker->numberBetween(1, min(4, $products->count())));

            if ($selectedProducts instanceof Product) {
                $selectedProducts = collect([$selectedProducts]);
            }

            $items = $selectedProducts->map(function (Product $product) use ($faker) {
                $quantity = $faker->numberBetween(1, 4);
                $unitPrice = (float) $product->current_price;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $quantity * $unitPrice,
                ];
            });

            $subtotal = $items->sum('line_total');
            $shippingFee = $subtotal >= 300000 ? 0 : 30000;
            $paymentStatus = match ($status) {
                'completed' => 'paid',
                'cancelled' => $faker->randomElement(['unpaid', 'refunded']),
                default => 'unpaid',
            };

            DocumentTransaction::run(function () use ($faker, $customer, $status, $paymentStatus, $createdAt, $code, $items, $subtotal, $shippingFee) {
                $orderId = (string) DB::table('orders')->insertGetId([
                    'code' => $code,
                    'user_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_phone' => $faker->numerify('09########'),
                    'customer_email' => $customer->email,
                    'shipping_address' => $faker->address(),
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'shipping_fee' => $shippingFee,
                    'total_amount' => $subtotal + $shippingFee,
                    'customer_note' => $faker->boolean(30) ? $faker->sentence() : null,
                    'cancel_reason' => $status === 'cancelled' ? 'Khách hàng thay đổi nhu cầu.' : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($items as $item) {
                    DB::table('order_items')->insert($item + [
                        'order_id' => $orderId,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                DB::table('payments')->insert([
                    'order_id' => $orderId,
                    'method' => $faker->randomElement(['cod', 'bank_transfer']),
                    'amount' => $subtotal + $shippingFee,
                    'status' => match ($paymentStatus) {
                        'paid' => 'completed',
                        'refunded' => 'refunded',
                        default => 'pending',
                    },
                    'paid_at' => $paymentStatus === 'paid' ? $createdAt->copy()->addHours(2) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            });
        }

        $this->command?->info('Đã tạo 12 khách hàng và 30 đơn hàng mẫu bằng Faker.');
    }
}
