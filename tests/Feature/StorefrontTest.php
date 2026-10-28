<?php

namespace Tests\Feature;

use App\Models\InventoryLot;
use App\Models\Order;
use App\Models\Product;
use App\Models\RescueCampaign;
use App\Models\Review;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_storefront_is_available(): void
    {
        $this->get('/')->assertOk()->assertSee('MiniMart')->assertSee('Trợ lý AI MiniMart');
    }

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_but_admin_can(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $this->actingAs($customer)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_checkout_creates_order_payment_and_deducts_stock(): void
    {
        $product = Product::with('inventoryLots')->firstOrFail();
        $before = $product->stock;
        $this->withSession(['cart' => [$product->id => 2]])->post('/thanh-toan', [
            'customer_name' => 'Khách kiểm thử', 'customer_phone' => '0901234567',
            'customer_email' => 'buyer@example.com', 'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
            'customer_note' => '', 'payment' => 'cod',
        ])->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sale']);
        $this->assertSame($before - 2, (float) InventoryLot::where('product_id', $product->id)->sum('quantity'));
    }

    public function test_vnpay_checkout_redirects_to_signed_sandbox_url(): void
    {
        config()->set('services.vnpay', [
            'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'tmn_code' => 'TESTCODE', 'hash_secret' => 'test-secret', 'return_url' => null,
        ]);
        $product = Product::firstOrFail();

        $response = $this->withSession(['cart' => [$product->id => 1]])->post('/thanh-toan', [
            'customer_name' => 'Khách VNPay', 'customer_phone' => '0901234567',
            'customer_email' => 'vnpay@example.com', 'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
            'customer_note' => '', 'payment' => 'vnpay',
        ]);

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?', $location);
        $this->assertStringContainsString('vnp_SecureHash=', $location);
        $this->assertDatabaseHas('payments', ['method' => 'online', 'status' => 'pending']);
    }

    public function test_valid_vnpay_callback_marks_order_as_paid(): void
    {
        config()->set('services.vnpay.hash_secret', 'test-secret');
        config()->set('services.vnpay.tmn_code', 'TESTCODE');
        config()->set('services.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $this->test_vnpay_checkout_redirects_to_signed_sandbox_url();
        $order = Order::query()->latest('id')->firstOrFail();
        $params = [
            'vnp_Amount' => (string) round((float) $order->total_amount * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TransactionNo' => '12345678',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => (string) $order->id,
        ];
        ksort($params);
        $params['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($params), 'test-secret');

        $this->get(route('vnpay.return', $params))->assertOk()->assertSee('Thanh toán thành công');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id, 'status' => 'completed', 'transaction_id' => '12345678',
        ]);
    }

    public function test_address_api_returns_normalized_provinces_and_wards(): void
    {
        Cache::flush();
        Http::fake([
            '*/provinces' => Http::response(['provinces' => [['code' => '79', 'name' => 'Thành phố Hồ Chí Minh']]]),
            '*/provinces/79/communes' => Http::response(['communes' => [['code' => '26734', 'name' => 'Phường Sài Gòn']]]),
        ]);

        $this->getJson(route('address.provinces'))->assertOk()
            ->assertExactJson([['code' => '79', 'name' => 'Thành phố Hồ Chí Minh']]);
        $this->getJson(route('address.wards', '79'))->assertOk()
            ->assertExactJson([['code' => '26734', 'name' => 'Phường Sài Gòn']]);
    }

    public function test_logged_in_customer_can_view_their_order_history_and_profile(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $product = Product::firstOrFail();

        $this->actingAs($customer)->withSession(['cart' => [$product->id => 1]])->post('/thanh-toan', [
            'customer_name' => $customer->name, 'customer_phone' => '0901234567',
            'customer_email' => $customer->email, 'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
            'customer_note' => '', 'payment' => 'cod',
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame($customer->id, $order->user_id);
        $this->get(route('account.orders.index'))->assertOk()->assertSee($order->code);
        $this->get(route('account.orders.show', $order->code))->assertOk()->assertSee($order->shipping_address);
        $this->get(route('account.profile'))->assertOk()->assertSee($customer->email);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $other = User::where('email', 'manager@minimart.test')->firstOrFail();
        $order = Order::query()->create([
            'user_id' => $other->id, 'code' => 'MM-PRIVATE', 'customer_name' => 'Khách khác',
            'customer_phone' => '0900000000', 'customer_email' => $other->email,
            'shipping_address' => 'Địa chỉ riêng tư của khách khác', 'subtotal' => 100000,
            'shipping_fee' => 0, 'total_amount' => 100000,
        ]);

        $this->actingAs($customer)->get(route('account.orders.show', $order->code))->assertNotFound();
    }

    public function test_only_super_admin_can_manage_users(): void
    {
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $manager = User::where('email', 'manager@minimart.test')->firstOrFail();

        $this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Tài khoản hệ thống');
    }

    public function test_super_admin_can_create_and_lock_a_customer_account(): void
    {
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $customerRole = Role::where('slug', 'customer')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Khách mới', 'email' => 'new-customer@example.com', 'phone' => '0987654321',
            'role_id' => $customerRole->id, 'is_active' => 1,
            'password' => 'Password@456', 'password_confirmation' => 'Password@456',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'new-customer@example.com')->firstOrFail();
        $this->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
            'role_id' => $customerRole->id, 'is_active' => 0, 'password' => '', 'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_admin_can_launch_rescue_campaign_and_checkout_tracks_impact(): void
    {
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $product = Product::with('inventoryLots')->firstOrFail();
        $lot = $product->inventoryLots->firstOrFail();
        $lot->update(['expires_at' => today()->addDays(3)]);

        $this->actingAs($admin)->post(route('admin.rescue.store', $lot), ['discount_percent' => 40])
            ->assertRedirect();
        $campaign = RescueCampaign::firstOrFail();
        $this->assertEquals(round((float) $product->selling_price * .6, -3), (float) $campaign->rescue_price);
        $this->get(route('rescue.index'))->assertOk()->assertSee($product->name);

        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $this->actingAs($customer)->withSession(['cart' => [$product->id => 1]])->post('/thanh-toan', [
            'customer_name' => $customer->name, 'customer_phone' => '0901234567',
            'customer_email' => $customer->email, 'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
            'customer_note' => '', 'payment' => 'cod',
        ])->assertRedirect();
        $this->assertSame(1.0, (float) $campaign->fresh()->rescued_quantity);
        $this->assertSame(0, $customer->fresh()->green_points);

        $order = Order::query()->latest('id')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'completed', 'payment_status' => 'paid'])->assertRedirect();
        $this->assertSame(10, $customer->fresh()->green_points);
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'cancelled', 'payment_status' => 'refunded'])->assertRedirect();
        $this->assertSame(0, $customer->fresh()->green_points);
        $this->assertSame(0.0, (float) $campaign->fresh()->rescued_quantity);
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['status' => 'pending', 'payment_status' => 'unpaid'])->assertSessionHasErrors('status');
    }

    public function test_customer_can_reorder_their_previous_order(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $product = Product::firstOrFail();
        $order = Order::create(['user_id' => $customer->id, 'code' => 'MM-REORDER', 'customer_name' => $customer->name, 'customer_phone' => '0901234567', 'customer_email' => $customer->email, 'shipping_address' => 'TP.HCM', 'subtotal' => 100000, 'shipping_fee' => 0, 'total_amount' => 100000]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => 2, 'unit_price' => 50000, 'line_total' => 100000]);
        $this->actingAs($customer)->post(route('account.orders.reorder', $order->code))->assertRedirect(route('cart.index'));
        $this->assertSame(2, session('cart')[$product->id]);
    }

    public function test_only_verified_buyer_of_completed_order_can_review(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $product = Product::firstOrFail();
        $order = Order::create(['user_id' => $customer->id, 'code' => 'MM-REVIEW', 'customer_name' => $customer->name, 'customer_phone' => '0901234567', 'customer_email' => $customer->email, 'shipping_address' => 'TP.HCM', 'status' => 'completed', 'payment_status' => 'paid', 'subtotal' => 100000, 'shipping_fee' => 0, 'total_amount' => 100000]);
        $item = $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => 1, 'unit_price' => 100000, 'line_total' => 100000]);
        $this->actingAs($customer)->post(route('account.reviews.store', $item), ['rating' => 5, 'content' => 'Sản phẩm rất tươi và đóng gói cẩn thận.'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['order_item_id' => $item->id, 'status' => 'pending', 'rating' => 5]);
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $review = Review::firstOrFail();
        $this->actingAs($admin)->patch(route('admin.reviews.update', $review), ['status' => 'approved'])->assertRedirect();
        $this->get(route('products.show', $product))->assertOk()->assertSee('Đã mua hàng');
    }

    public function test_cooking_assistant_page_explains_missing_api_key(): void
    {
        config()->set('services.gemini.api_key', null);
        $this->get(route('cooking-assistant.index'))->assertOk()->assertSee('Chưa có Gemini API key');
        $this->postJson(route('cooking-assistant.ask'), ['message' => 'Tôi muốn ăn cá kho'])
            ->assertStatus(503)->assertJsonFragment(['message' => 'Trợ lý chưa được cấu hình Gemini API key.']);
    }

    public function test_cooking_assistant_matches_catalog_products_and_adds_them_to_cart(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-3.5-flash-lite');
        $product = Product::with('inventoryLots')->firstOrFail();
        Http::fake(['generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'dish' => 'Cá kho', 'answer' => 'Đây là nguyên liệu cho món cá kho.',
                'ingredients' => ['Cá', 'Nước mắm', 'Tiêu'], 'steps' => ['Ướp cá', 'Kho nhỏ lửa'],
                'product_ids' => [$product->id, 999999],
            ], JSON_UNESCAPED_UNICODE)]]]]],
        ])]);

        $this->postJson(route('cooking-assistant.ask'), ['message' => 'Tôi muốn ăn cá kho'])
            ->assertOk()->assertJsonPath('dish', 'Cá kho')->assertJsonCount(1, 'products')->assertJsonPath('products.0.id', $product->id);
        $this->postJson(route('cooking-assistant.add-products'), ['product_ids' => [$product->id]])
            ->assertOk()->assertJsonPath('cart_count', 1);
        $this->assertSame(1, session('cart')[$product->id]);
    }

    private function checkout(array $cart, string $payment = 'cod', ?User $user = null)
    {
        if ($user) {
            $this->actingAs($user);
        }

        return $this->withSession(['cart' => $cart])->post('/thanh-toan', [
            'customer_name' => 'Khách kiểm thử', 'customer_phone' => '0901234567',
            'customer_email' => $user?->email ?? 'buyer@example.com', 'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
            'customer_note' => '', 'payment' => $payment,
        ]);
    }

    private function vnpayCallback(Order $order, string $responseCode, string $transactionNo)
    {
        $params = [
            'vnp_Amount' => (string) round((float) $order->total_amount * 100),
            'vnp_ResponseCode' => $responseCode, 'vnp_TransactionNo' => $transactionNo,
            'vnp_TransactionStatus' => $responseCode === '00' ? '00' : '02', 'vnp_TxnRef' => (string) $order->id,
        ];
        ksort($params);
        $params['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($params), 'test-secret');

        return $this->getJson(route('vnpay.ipn', $params));
    }

    public function test_rescue_price_only_applies_to_remaining_campaign_quantity(): void
    {
        $product = Product::with('inventoryLots')->firstOrFail();
        $lot = $product->inventoryLots->firstOrFail();
        $lot->update(['quantity' => 50, 'reserved_quantity' => 0, 'expires_at' => today()->addDays(2)]);
        InventoryLot::where('product_id', $product->id)->where('id', '!=', $lot->id)->delete();
        $campaign = RescueCampaign::create([
            'inventory_lot_id' => $lot->id, 'product_id' => $product->id, 'discount_percent' => 50,
            'original_price' => 20000, 'rescue_price' => 10000, 'initial_quantity' => 10, 'rescued_quantity' => 8,
            'is_active' => true, 'starts_at' => now()->subHour(), 'ends_at' => today()->addDays(2)->endOfDay(),
        ]);
        $regular = Product::find($product->id)->regular_price;

        $this->checkout([$product->id => 5])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertEquals(2 * 10000 + 3 * $regular, (float) $order->subtotal);
        $this->assertSame(10.0, (float) $campaign->fresh()->rescued_quantity);
        $this->assertSame(2.0, abs((float) DB::table('stock_movements')->whereNotNull('rescue_campaign_id')->sum('quantity')));
    }

    public function test_cancelled_vnpay_payments_are_recorded_and_restock(): void
    {
        config()->set('services.vnpay', [
            'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'tmn_code' => 'TESTCODE', 'hash_secret' => 'test-secret', 'return_url' => null,
        ]);
        $product = Product::with('inventoryLots')->firstOrFail();
        $before = $product->stock;

        foreach ([1, 2] as $attempt) {
            $this->checkout([$product->id => 2], 'vnpay')->assertRedirect();
            $order = Order::query()->latest('id')->firstOrFail();
            $this->vnpayCallback($order, '24', '0')->assertOk()->assertJsonPath('RspCode', '00');
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'failed']);
        }

        $this->assertSame(2, DB::table('payments')->where('status', 'failed')->whereNull('transaction_id')->count());
        $this->assertSame($before, Product::with('inventoryLots')->find($product->id)->stock);
    }

    public function test_abandoned_vnpay_orders_expire_and_restock(): void
    {
        config()->set('services.vnpay', [
            'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'tmn_code' => 'TESTCODE', 'hash_secret' => 'test-secret', 'return_url' => null,
        ]);
        $product = Product::with('inventoryLots')->firstOrFail();
        $before = $product->stock;
        $this->checkout([$product->id => 2], 'vnpay')->assertRedirect();
        $order = Order::query()->latest('id')->firstOrFail();

        $this->artisan('orders:expire-unpaid')->assertSuccessful();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);

        $this->travel(31)->minutes();
        $this->artisan('orders:expire-unpaid')->assertSuccessful();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'failed', 'cancel_reason' => 'Quá hạn thanh toán VNPay']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'failed']);
        $this->assertSame($before, Product::with('inventoryLots')->find($product->id)->stock);

        // IPN đến sau khi đơn đã hết hạn không được ghi nhận thanh toán.
        $this->vnpayCallback($order->fresh(), '00', '123456')->assertOk();
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'failed', 'transaction_id' => null]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'failed']);
    }

    public function test_reopening_campaign_keeps_rescued_history_active(): void
    {
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $lot = InventoryLot::firstOrFail();
        $lot->update(['quantity' => 10, 'reserved_quantity' => 0, 'expires_at' => today()]);

        $this->actingAs($admin)->post(route('admin.rescue.store', $lot), ['discount_percent' => 30])->assertRedirect();
        $campaign = RescueCampaign::firstOrFail();
        $campaign->update(['rescued_quantity' => 6]);
        $lot->update(['quantity' => 4]);

        $this->actingAs($admin)->post(route('admin.rescue.store', $lot), ['discount_percent' => 50])->assertRedirect();
        $this->assertSame(10.0, (float) $campaign->fresh()->initial_quantity);
        $this->assertSame(1, RescueCampaign::active()->count());
    }

    public function test_guest_orders_are_only_linked_to_verified_email(): void
    {
        $order = Order::create(['code' => 'MM-GUEST', 'customer_name' => 'Khách vãng lai', 'customer_phone' => '0901234567', 'customer_email' => 'victim@example.com', 'shipping_address' => 'Địa chỉ của nạn nhân', 'subtotal' => 100000, 'shipping_fee' => 0, 'total_amount' => 100000]);
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();

        $this->actingAs($customer)->patch(route('account.profile.update'), ['name' => $customer->name, 'email' => 'victim@example.com'])->assertRedirect();
        $this->assertNull($customer->fresh()->email_verified_at);
        $this->actingAs($customer->fresh())->get(route('account.orders.show', $order->code))->assertNotFound();

        $customer->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($customer->fresh())->get(route('account.orders.show', $order->code))->assertOk();
    }

    public function test_reorder_respects_cart_limit(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $product = Product::firstOrFail();
        InventoryLot::where('product_id', $product->id)->update(['quantity' => 500, 'reserved_quantity' => 0]);
        $order = Order::create(['user_id' => $customer->id, 'code' => 'MM-BULK', 'customer_name' => $customer->name, 'customer_phone' => '0901234567', 'customer_email' => $customer->email, 'shipping_address' => 'TP.HCM', 'subtotal' => 100000, 'shipping_fee' => 0, 'total_amount' => 100000]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => 80, 'unit_price' => 1000, 'line_total' => 80000]);

        $this->actingAs($customer)->withSession(['cart' => [$product->id => 80]])->post(route('account.orders.reorder', $order->code))->assertRedirect();
        $this->assertSame(99, session('cart')[$product->id]);
    }

    public function test_dashboard_shows_six_distinct_months_at_month_end(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('monthlyRevenue', fn ($months) => $months->pluck('label')->all() === ['T05', 'T06', 'T07', 'T08', 'T09', 'T10']);
    }

    public function test_address_api_error_returns_service_unavailable(): void
    {
        Cache::flush();
        Http::fake(['*/provinces' => Http::response([], 500)]);

        $this->getJson(route('address.provinces'))->assertStatus(503);
    }

    public function test_home_slider_shows_category_deals_and_buy_now_goes_to_cart(): void
    {
        $response = $this->get('/')->assertOk()->assertSee('hero-slider')->assertSee('Đặt hàng ngay');
        $slides = $response->viewData('heroSlides');
        $this->assertNotEmpty($slides);
        $product = $slides->first()['product'];

        $this->post(route('cart.store', $product), ['quantity' => 1, 'buy_now' => 1])->assertRedirect(route('cart.index'));
        $this->assertSame(1, session('cart')[$product->id]);
    }

    public function test_shop_filters_by_brand_price_and_sale(): void
    {
        $names = fn ($response) => $response->viewData('products')->pluck('name')->sort()->values()->all();

        $this->get(route('products.index', ['brand' => 'vietgap-farm']))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->total() === 2);
        $this->assertSame(['Gạo ST25 túi 5kg'], $names($this->get(route('products.index', ['brand' => 'neptune']))));

        $under50 = $this->get(route('products.index', ['price' => 'duoi-50k']));
        $this->assertTrue($under50->viewData('products')->every(fn ($p) => $p->regular_price < 50000));
        $this->assertSame(4, $under50->viewData('products')->total());

        $custom = $this->get(route('products.index', ['min_price' => 200000, 'max_price' => 100000, 'sort' => 'price_asc']));
        $prices = $custom->viewData('products')->getCollection()->map(fn ($p) => $p->regular_price)->all();
        $this->assertNotEmpty($prices);
        $this->assertTrue(collect($prices)->every(fn ($price) => $price >= 100000 && $price <= 200000));
        $this->assertSame($prices, collect($prices)->sort()->values()->all());

        $this->get(route('products.index', ['sale' => 1, 'category' => 'rau-cu-qua']))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->every(fn ($p) => $p->regular_price < (float) $p->selling_price))
            ->assertSee('Đang giảm giá');
    }

    public function test_business_errors_return_to_page_with_toast_and_errors_have_branded_pages(): void
    {
        $product = Product::with('inventoryLots')->firstOrFail();
        InventoryLot::where('product_id', $product->id)->update(['quantity' => 2, 'reserved_quantity' => 0]);

        $this->from(route('products.index'))->post(route('cart.store', $product), ['quantity' => 5])
            ->assertRedirect(route('products.index'))->assertSessionHasErrors(['message' => 'Số lượng yêu cầu vượt quá tồn kho.']);
        $this->get(route('products.index'))->assertSee('flash flash-error', false)->assertSee('Số lượng yêu cầu vượt quá tồn kho.');

        $this->get('/khong-ton-tai')->assertNotFound()->assertSee('Trang này đã “hết hàng”', false);
    }
}
