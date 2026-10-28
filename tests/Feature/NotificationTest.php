<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
        $this->customer = User::where('email', 'customer@minimart.test')->firstOrFail();
        $this->admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $this->staff = User::where('email', 'staff@minimart.test')->firstOrFail();
    }

    private function placeOrder(): Order
    {
        $this->actingAs($this->customer)->withSession(['cart' => [Product::firstOrFail()->id => 1]])->post('/thanh-toan', [
            'customer_name' => 'Khách', 'customer_phone' => '0901234567', 'shipping_address' => '12 Nguyễn Huệ, Quận 1, TP.HCM', 'payment' => 'cod',
        ])->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    public function test_staff_are_notified_of_new_orders_and_see_pending_count(): void
    {
        $order = $this->placeOrder();

        foreach ([$this->admin, $this->staff] as $user) {
            $this->assertSame('new_order', $user->fresh()->unreadNotifications()->first()->data['kind']);
        }
        $this->assertSame(0, $this->customer->notifications()->count());
        $this->assertSame(0, User::where('email', 'shipper@minimart.test')->firstOrFail()->notifications()->count());

        $this->actingAs($this->staff)->getJson(route('notifications.index'))->assertOk()
            ->assertJsonPath('unread', 1)->assertJsonPath('pending_orders', 1)
            ->assertJsonPath('items.0.title', 'Đơn mới '.$order->code);
        $this->get(route('admin.dashboard'))->assertSee('data-pending-orders', false)->assertSee('Đơn mới '.$order->code);
    }

    public function test_customer_is_notified_when_shop_updates_order_and_can_open_it(): void
    {
        $order = $this->placeOrder();
        $this->actingAs($this->admin)->patch(route('admin.orders.update', $order), ['status' => 'confirmed', 'payment_status' => 'unpaid'])->assertSessionHasNoErrors();

        $notification = $this->customer->fresh()->unreadNotifications()->firstOrFail();
        $this->assertSame('Đơn '.$order->code.': Shop đã xác nhận đơn hàng', $notification->data['title']);
        $this->actingAs($this->customer)->getJson(route('notifications.index'))->assertJsonPath('unread', 1)->assertJsonPath('pending_orders', null);
        $this->get(route('home'))->assertSee('Shop đã xác nhận đơn hàng');

        $this->actingAs($this->staff)->get(route('notifications.open', $notification->id))->assertNotFound();
        $this->actingAs($this->customer)->get(route('notifications.open', $notification->id))->assertRedirect(route('account.orders.show', $order->code));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_customer_cancellation_notifies_staff_but_not_the_customer(): void
    {
        $order = $this->placeOrder();
        $this->post(route('account.orders.cancel', $order->code), ['reason' => Order::CANCEL_REASONS[3]])->assertRedirect();

        $this->assertSame(0, $this->customer->fresh()->notifications()->count());
        $this->assertTrue($this->staff->fresh()->unreadNotifications->contains(fn ($n) => $n->data['kind'] === 'order_cancelled'));

        $this->actingAs($this->staff)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $this->staff->fresh()->unreadNotifications()->count());
    }

    public function test_notification_feed_requires_login(): void
    {
        $this->getJson(route('notifications.index'))->assertUnauthorized();
    }
}
