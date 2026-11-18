<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshMongoDatabase;

    private User $customer;

    private User $admin;

    private User $shipper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
        $this->customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $this->admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $this->shipper = User::where('email', 'shipper@minimart.test')->firstOrFail();
    }

    private function placeOrder(): Order
    {
        $this->actingAs($this->customer)->withSession(['cart' => [Product::firstOrFail()->id => 1]])->post('/thanh-toan', [
            'customer_name' => 'Khách', 'customer_phone' => '0901234567', 'shipping_address' => '12 Nguyễn Huệ, Quận 1, TP.HCM', 'payment' => 'cod',
        ])->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    private function adminUpdate(Order $order, string $status, ?string $shipperId = null)
    {
        return $this->actingAs($this->admin)->patch(route('admin.orders.update', $order), [
            'status' => $status, 'payment_status' => $order->fresh()->payment_status, 'shipper_id' => $shipperId,
        ]);
    }

    public function test_customer_follows_order_from_confirmation_to_delivery(): void
    {
        $order = $this->placeOrder();
        $this->get(route('account.orders.show', $order->code))->assertSee('Đặt hàng thành công')->assertSee('Shop sẽ xác nhận đơn sớm');

        $this->travelTo(now()->setTime(10, 0));
        $this->adminUpdate($order, 'confirmed', $this->shipper->id)->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertSame('12:00', $order->estimated_delivery_at->format('H:i'));
        $this->assertSame($this->shipper->id, $order->shipper_id);

        $this->actingAs($this->customer)->get(route('account.orders.show', $order->code))
            ->assertSee('Dự kiến nhận hàng trước')->assertSee('12:00 hôm nay')
            ->assertSee('Shipper Minh Tuấn')->assertSee('tel:0909123456', false)
            ->assertSee('Đã giao cho shipper Shipper Minh Tuấn');
        $this->get(route('account.orders.index', ['tab' => 'pickup']))->assertViewHas('orders', fn ($orders) => $orders->contains($order));
        $this->get(route('account.orders.index', ['tab' => 'pending']))->assertViewHas('orders', fn ($orders) => ! $orders->contains($order));

        $this->actingAs($this->shipper)->get(route('shipper.index'))->assertOk()->assertSee($order->code)->assertSee('Thu ');
        $this->post(route('shipper.pickup', $order))->assertRedirect();
        $this->post(route('shipper.update', $order), ['note' => 'Đang ở cầu Ánh Sao, còn 10 phút'])->assertRedirect();
        $this->actingAs($this->customer)->get(route('account.orders.index', ['tab' => 'shipping']))->assertViewHas('orders', fn ($orders) => $orders->contains($order));
        $this->get(route('account.orders.show', $order->code))->assertSee('Đang ở cầu Ánh Sao, còn 10 phút');

        $this->actingAs($this->shipper)->post(route('shipper.deliver', $order))->assertRedirect();
        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->delivered_at);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'method' => 'cod', 'status' => 'completed']);
        $this->actingAs($this->customer)->get(route('account.orders.index', ['tab' => 'completed']))->assertViewHas('orders', fn ($orders) => $orders->contains($order));
    }

    public function test_shipper_can_only_handle_orders_assigned_to_them(): void
    {
        $order = $this->placeOrder();
        $this->actingAs($this->shipper)->post(route('shipper.pickup', $order))->assertForbidden();

        $this->adminUpdate($order, 'confirmed', $this->shipper->id);
        $this->actingAs($this->shipper)->post(route('shipper.deliver', $order))->assertSessionHasErrors('message');
        $this->assertSame('confirmed', $order->fresh()->status);

        $this->actingAs($this->customer)->get(route('shipper.index'))->assertForbidden();
        $this->actingAs($this->shipper)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->admin)->patch(route('admin.orders.update', $order), [
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'shipper_id' => $this->customer->id,
        ])->assertSessionHasErrors('shipper_id');
    }

    public function test_customer_requests_return_and_admin_refunds(): void
    {
        $order = $this->placeOrder();
        $this->adminUpdate($order, 'completed');
        $this->actingAs($this->customer)->get(route('account.orders.show', $order->code))->assertSee('Yêu cầu trả hàng/hoàn tiền');

        $this->post(route('account.orders.return', $order->code), ['return_reason' => 'Sản phẩm bị hư hỏng, dập nát', 'return_detail' => 'Trứng bị vỡ 3 quả'])
            ->assertRedirect(route('account.orders.show', $order->code));
        $this->post(route('account.orders.return', $order->code), ['return_reason' => 'Lý do khác'])->assertSessionHasErrors('return_reason');
        $this->get(route('account.orders.index', ['tab' => 'returns']))->assertViewHas('orders', fn ($orders) => $orders->contains($order))->assertSee('Chờ shop duyệt');
        $this->get(route('account.orders.index', ['tab' => 'completed']))->assertViewHas('orders', fn ($orders) => ! $orders->contains($order));

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'return_pending']))->assertSee($order->code);
        $this->patch(route('admin.orders.return', $order), ['decision' => 'reject'])->assertSessionHasErrors('admin_note');
        $this->patch(route('admin.orders.return', $order), ['decision' => 'approve', 'admin_note' => 'Shop xin lỗi bạn'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('returned', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertDatabaseHas('return_requests', ['order_id' => $order->id, 'status' => 'approved', 'refund_amount' => $order->total_amount]);
        $this->adminUpdate($order, 'completed')->assertSessionHasErrors('status');
        $this->actingAs($this->customer)->get(route('account.orders.show', $order->code))->assertSee('Đã chấp nhận, hoàn tiền')->assertSee('Shop xin lỗi bạn');
    }

    public function test_return_window_closes_after_three_days(): void
    {
        $order = $this->placeOrder();
        $this->adminUpdate($order, 'completed');
        $this->travel(Order::RETURN_WINDOW_DAYS + 1)->days();

        $this->actingAs($this->customer)->post(route('account.orders.return', $order->code), ['return_reason' => 'Lý do khác'])
            ->assertSessionHasErrors('return_reason');
        $this->assertDatabaseCount('return_requests', 0);
    }

    public function test_cancelling_adds_a_tracking_event_and_lands_in_cancelled_tab(): void
    {
        $order = $this->placeOrder();
        $this->post(route('account.orders.cancel', $order->code), ['reason' => Order::CANCEL_REASONS[3]])->assertRedirect();

        $this->assertDatabaseHas('order_tracking_events', ['order_id' => $order->id, 'title' => 'Đơn hàng đã bị hủy']);
        $this->get(route('account.orders.index', ['tab' => 'cancelled']))->assertViewHas('orders', fn ($orders) => $orders->contains($order));
        $this->get(route('account.orders.index'))->assertViewHas('orders', fn ($orders) => $orders->contains($order));
    }
}
