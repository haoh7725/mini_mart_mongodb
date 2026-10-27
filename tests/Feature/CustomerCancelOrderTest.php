<?php

namespace Tests\Feature;

use App\Models\InventoryLot;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerCancelOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $this->product = Product::firstOrFail();
    }

    private function placeOrder(): Order
    {
        $this->actingAs($this->customer)->withSession(['cart' => [$this->product->id => 3]])->post('/thanh-toan', [
            'customer_name' => $this->customer->name, 'customer_phone' => '0901234567',
            'customer_email' => $this->customer->email, 'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
            'customer_note' => '', 'payment' => 'cod',
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function stock(): float
    {
        return (float) InventoryLot::where('product_id', $this->product->id)->sum('quantity');
    }

    public function test_customer_can_cancel_pending_order_and_stock_is_restored(): void
    {
        $before = $this->stock();
        $order = $this->placeOrder();
        $this->assertSame($before - 3, $this->stock());

        $this->get(route('account.orders.show', $order->code))->assertSee('Hủy đơn hàng');
        $this->post(route('account.orders.cancel', $order->code), ['reason' => Order::CANCEL_REASONS[3], 'reason_detail' => 'Đặt nhầm'])
            ->assertRedirect(route('account.orders.show', $order->code));

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame(Order::CANCEL_REASONS[3].': Đặt nhầm', $order->cancel_reason);
        $this->assertSame($before, $this->stock());
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'failed']);
        $this->get(route('account.orders.show', $order->code))->assertSee('Đơn đã hủy')->assertDontSee('Xác nhận hủy đơn');

        // Bấm hủy lần nữa không hoàn kho thêm.
        $this->post(route('account.orders.cancel', $order->code), ['reason' => Order::CANCEL_REASONS[0]])->assertSessionHasErrors('order');
        $this->assertSame($before, $this->stock());
    }

    public function test_shipping_or_paid_orders_cannot_be_cancelled(): void
    {
        $order = $this->placeOrder();
        $stock = $this->stock();

        $order->update(['status' => 'shipping']);
        $this->post(route('account.orders.cancel', $order->code), ['reason' => Order::CANCEL_REASONS[0]])->assertSessionHasErrors('order');

        $order->update(['status' => 'pending', 'payment_status' => 'paid']);
        $this->get(route('account.orders.show', $order->code))->assertSee('hotline 1900 6868')->assertDontSee('Xác nhận hủy đơn');
        $this->post(route('account.orders.cancel', $order->code), ['reason' => Order::CANCEL_REASONS[0]])->assertSessionHasErrors('order');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame($stock, $this->stock());
    }

    public function test_reason_is_required_and_other_customers_cannot_cancel(): void
    {
        $order = $this->placeOrder();

        $this->post(route('account.orders.cancel', $order->code), [])->assertSessionHasErrors('reason');
        $this->post(route('account.orders.cancel', $order->code), ['reason' => 'lý do bịa'])->assertSessionHasErrors('reason');

        $other = User::where('email', 'manager@minimart.test')->firstOrFail();
        $this->actingAs($other)->post(route('account.orders.cancel', $order->code), ['reason' => Order::CANCEL_REASONS[0]])->assertNotFound();
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, DB::table('stock_movements')->where('type', 'return')->count());
    }
}
