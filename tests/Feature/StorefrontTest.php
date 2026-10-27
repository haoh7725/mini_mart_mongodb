<?php

namespace Tests\Feature;

use App\Models\InventoryLot;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\RescueCampaign;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        $this->assertSame(10, $customer->fresh()->green_points);
    }

    public function test_customer_can_reorder_their_previous_order(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $product = Product::firstOrFail();
        $order = Order::create(['user_id'=>$customer->id,'code'=>'MM-REORDER','customer_name'=>$customer->name,'customer_phone'=>'0901234567','customer_email'=>$customer->email,'shipping_address'=>'TP.HCM','subtotal'=>100000,'shipping_fee'=>0,'total_amount'=>100000]);
        $order->items()->create(['product_id'=>$product->id,'product_name'=>$product->name,'sku'=>$product->sku,'quantity'=>2,'unit_price'=>50000,'line_total'=>100000]);
        $this->actingAs($customer)->post(route('account.orders.reorder',$order->code))->assertRedirect(route('cart.index'));
        $this->assertSame(2, session('cart')[$product->id]);
    }

    public function test_only_verified_buyer_of_completed_order_can_review(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $product = Product::firstOrFail();
        $order = Order::create(['user_id'=>$customer->id,'code'=>'MM-REVIEW','customer_name'=>$customer->name,'customer_phone'=>'0901234567','customer_email'=>$customer->email,'shipping_address'=>'TP.HCM','status'=>'completed','payment_status'=>'paid','subtotal'=>100000,'shipping_fee'=>0,'total_amount'=>100000]);
        $item = $order->items()->create(['product_id'=>$product->id,'product_name'=>$product->name,'sku'=>$product->sku,'quantity'=>1,'unit_price'=>100000,'line_total'=>100000]);
        $this->actingAs($customer)->post(route('account.reviews.store',$item), ['rating'=>5,'content'=>'Sản phẩm rất tươi và đóng gói cẩn thận.'])->assertRedirect();
        $this->assertDatabaseHas('reviews',['order_item_id'=>$item->id,'status'=>'pending','rating'=>5]);
        $admin = User::where('email','admin@minimart.test')->firstOrFail();
        $review = Review::firstOrFail();
        $this->actingAs($admin)->patch(route('admin.reviews.update',$review),['status'=>'approved'])->assertRedirect();
        $this->get(route('products.show',$product))->assertOk()->assertSee('Đã mua hàng');
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
}
