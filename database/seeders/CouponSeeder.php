<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        // Do not overwrite limits, usage or activation chosen by the administrator.
        Coupon::firstOrCreate(['code' => 'WELCOME15'], [
            'type' => 'percentage', 'value' => 15, 'minimum_order_amount' => 300000,
            'is_active' => true,
        ]);
    }
}
