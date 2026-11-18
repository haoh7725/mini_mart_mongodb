<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\InventoryLot;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\CouponSeeder;
use Database\Seeders\DatabaseSeeder;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshMongoDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->seed(DatabaseSeeder::class);
    }

    private function coupon(array $values = []): Coupon
    {
        return Coupon::create($values + ['code' => 'TEST', 'type' => 'percentage', 'value' => 10, 'minimum_order_amount' => 0, 'is_active' => true]);
    }

    private function checkout(string $code = 'TEST', string $payment = 'cod')
    {
        return $this->withSession(['cart' => [Product::firstOrFail()->id => 2]])->post('/thanh-toan', [
            'customer_name' => 'Test', 'customer_phone' => '0901234567', 'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
            'coupon_code' => $code, 'payment' => $payment,
        ]);
    }

    public function test_admin_can_type_a_code_or_leave_it_blank_to_generate_one(): void
    {
        $manager = User::where('email', 'manager@minimart.test')->firstOrFail();
        $data = ['type' => 'percentage', 'value' => 10, 'minimum_order_amount' => 0, 'is_active' => 1];

        $this->actingAs($manager)->post(route('admin.coupons.store'), $data + ['code' => ' tet2027 '])->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', ['code' => 'TET2027']);

        $this->post(route('admin.coupons.store'), $data + ['code' => ''])->assertSessionHasNoErrors();
        $generated = Coupon::latest('id')->firstOrFail();
        $this->assertMatchesRegularExpression('/^['.Coupon::CODE_ALPHABET.']{8}$/', $generated->code);

        $this->put(route('admin.coupons.update', $generated), $data + ['code' => ''])->assertSessionHasErrors('code');
        $this->assertMatchesRegularExpression('/^SALE-['.Coupon::CODE_ALPHABET.']{8}$/', Coupon::generateCode('SALE'));
    }

    public function test_discount_is_saved_and_cancellation_releases_usage_once(): void
    {
        $coupon = $this->coupon(['usage_limit' => 1]);
        $this->checkout(' test ')->assertRedirect();
        $order = Order::latest('id')->firstOrFail();
        $this->assertEquals(round($order->subtotal * .1, 2), $order->discount_amount);
        $this->assertEquals($order->subtotal - $order->discount_amount + $order->shipping_fee, $order->total_amount);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => $order->total_amount]);
        $this->assertEquals(1, $coupon->fresh()->used_count);
        $this->checkout()->assertSessionHasErrors('coupon_code');
        app(OrderService::class)->cancel($order);
        app(OrderService::class)->cancel($order);
        $this->assertEquals(0, $coupon->fresh()->used_count);
        $this->checkout()->assertRedirect();
        $this->assertEquals(1, $coupon->fresh()->used_count);
    }

    public function test_invalid_codes_do_not_create_orders_or_change_stock(): void
    {
        $coupon = $this->coupon();
        $stock = InventoryLot::sum('quantity');
        foreach ([['is_active' => false], ['is_active' => true, 'starts_at' => now()->addDay()], ['starts_at' => null, 'expires_at' => now()->subDay()], ['expires_at' => null, 'minimum_order_amount' => 999999999]] as $values) {
            $coupon->update($values);
            $this->checkout()->assertSessionHasErrors('coupon_code');
        }
        $this->checkout('MISSING')->assertSessionHasErrors('coupon_code');
        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals($stock, InventoryLot::sum('quantity'));
        $this->assertEquals(0, $coupon->fresh()->used_count);
    }

    public function test_preview_caps_discount_and_does_not_consume_usage(): void
    {
        $coupon = $this->coupon(['value' => 50, 'maximum_discount_amount' => 1000]);
        $this->withSession(['cart' => [Product::firstOrFail()->id => 2]])->postJson(route('checkout.coupon'), ['coupon_code' => 'TEST'])->assertOk()->assertJsonPath('discount', 1000);
        $this->assertEquals(0, $coupon->fresh()->used_count);
        $coupon->update(['type' => 'fixed', 'value' => 9999999, 'maximum_discount_amount' => null]);
        $this->assertEquals(50000, $coupon->discount(50000));
    }

    public function test_checkout_revalidates_after_preview(): void
    {
        $coupon = $this->coupon();
        $this->withSession(['cart' => [Product::firstOrFail()->id => 2]])->postJson(route('checkout.coupon'), ['coupon_code' => 'TEST'])->assertOk();
        $coupon->update(['is_active' => false]);
        $this->checkout()->assertSessionHasErrors('coupon_code');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_vnpay_uses_discounted_amount(): void
    {
        config(['services.vnpay.tmn_code' => 'TEST', 'services.vnpay.hash_secret' => 'test', 'services.vnpay.url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html']);
        $this->coupon();
        $response = $this->checkout('TEST', 'vnpay')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $order = Order::latest('id')->firstOrFail();
        $this->assertEquals(round($order->total_amount * 100), $query['vnp_Amount']);
    }

    public function test_admin_can_manage_coupons_and_staff_cannot(): void
    {
        $this->actingAs(User::where('email', 'staff@minimart.test')->firstOrFail())->get(route('admin.coupons.index'))->assertForbidden();
        $this->actingAs(User::where('email', 'manager@minimart.test')->firstOrFail());
        $this->get(route('admin.coupons.create'))->assertOk();
        $data = ['code' => ' save20 ', 'type' => 'percentage', 'value' => 20, 'minimum_order_amount' => 0, 'is_active' => 1];
        $this->post(route('admin.coupons.store'), $data)->assertRedirect();
        $coupon = Coupon::where('code', 'SAVE20')->firstOrFail();
        $this->get(route('admin.coupons.edit', $coupon))->assertOk();
        $this->get(route('admin.coupons.index'))->assertOk()->assertSee('SAVE20');
        $this->put(route('admin.coupons.update', $coupon), array_replace($data, ['is_active' => 0]))->assertRedirect();
        $this->assertFalse($coupon->fresh()->is_active);
        $this->post(route('admin.coupons.store'), array_replace($data, ['code' => 'BAD', 'value' => 101]))->assertSessionHasErrors('value');
    }

    public function test_checkout_page_renders_coupon_input_and_optional_code_works(): void
    {
        $this->withSession(['cart' => [Product::firstOrFail()->id => 2]])->get('/thanh-toan')->assertOk()->assertSee('coupon_code');
        $this->checkout('')->assertRedirect();
        $this->assertEquals(0, Order::latest('id')->firstOrFail()->discount_amount);
    }

    public function test_seeding_does_not_reactivate_or_reset_existing_coupon(): void
    {
        $coupon = Coupon::where('code', 'WELCOME15')->firstOrFail();
        $coupon->update(['is_active' => false]);
        $coupon->increment('used_count');
        $this->seed(CouponSeeder::class);
        $this->assertFalse($coupon->fresh()->is_active);
        $this->assertEquals(1, $coupon->fresh()->used_count);
    }

    public function test_zero_total_does_not_create_vnpay_payment_or_consume_coupon(): void
    {
        config(['services.vnpay.tmn_code' => 'TEST', 'services.vnpay.hash_secret' => 'test']);
        $coupon = $this->coupon(['type' => 'fixed', 'value' => 9999999]);
        Product::firstOrFail()->update(['selling_price' => 400000, 'sale_price' => null]);
        $this->checkout('TEST', 'vnpay')->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(0, $coupon->fresh()->used_count);
        $this->checkout()->assertRedirect();
        $this->assertEquals(0, Order::latest('id')->firstOrFail()->total_amount);
    }
}
