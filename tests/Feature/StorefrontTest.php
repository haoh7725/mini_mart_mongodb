<?php

namespace Tests\Feature;

use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->get('/')->assertOk()->assertSee('MiniMart');
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
}
