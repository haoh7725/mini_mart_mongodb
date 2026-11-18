<?php

namespace Tests\Feature;

use App\Models\InventoryLot;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\DocumentTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use MongoDB\Client;
use RuntimeException;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class MongoStorageTest extends TestCase
{
    use RefreshMongoDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_failed_nested_transaction_rolls_back_all_writes_and_notifications(): void
    {
        $callbacks = 0;
        try {
            DocumentTransaction::run(function () use (&$callbacks) {
                Product::firstOrFail()->update(['name' => 'Should roll back']);
                DocumentTransaction::run(fn () => DB::table('payments')->insert(['order_id' => 'rollback', 'amount' => 50000]));
                DB::afterCommit(function () use (&$callbacks) {
                    $callbacks++;
                });
                throw new RuntimeException('Expected failure');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Expected failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('products', ['name' => 'Should roll back']);
        $this->assertDatabaseMissing('payments', ['order_id' => 'rollback']);
        DocumentTransaction::run(fn () => DB::table('payments')->insert(['order_id' => 'next', 'amount' => 1]));
        $this->assertSame(0, $callbacks);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_write_conflict_retries_without_duplicate_after_commit_callbacks(): void
    {
        $lot = InventoryLot::firstOrFail();
        $attempts = 0;
        $callbacks = 0;
        $other = new Client(config('database.connections.mongodb.dsn'));
        $collection = $other->getDatabase(DB::connection()->getDatabaseName())->selectCollection('inventory_lots');

        DocumentTransaction::run(function () use ($lot, $collection, &$attempts, &$callbacks) {
            $attempts++;
            InventoryLot::findOrFail($lot->id);
            DB::afterCommit(function () use (&$callbacks) {
                $callbacks++;
            });
            if ($attempts === 1) {
                $collection->updateOne(['_id' => $lot->getRawOriginal('_id') ?? $lot->getRawOriginal('id')], ['$inc' => ['quantity' => 1]]);
            }
            InventoryLot::whereKey($lot->id)->decrement('quantity');
        });

        $this->assertSame(2, $attempts);
        $this->assertSame(1, $callbacks);
        $this->assertEquals($lot->quantity, $lot->fresh()->quantity);
    }

    public function test_password_reset_token_works_with_mongodb(): void
    {
        $user = User::where('email', 'customer@minimart.test')->firstOrFail();
        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));
        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'NewPassword@123', 'password_confirmation' => 'NewPassword@123',
        ])->assertSessionHasNoErrors();
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->assertTrue(Hash::check('NewPassword@123', $user->fresh()->password));
    }

    public function test_form_numeric_values_remain_numeric_for_price_filters_and_reports(): void
    {
        $product = Product::firstOrFail();
        $product->update(['selling_price' => '123000', 'sale_price' => null]);
        $order = Order::create([
            'code' => 'MM-MONGO-REPORT', 'customer_name' => 'MongoDB test', 'customer_phone' => '0901234567',
            'shipping_address' => 'TP.HCM', 'status' => 'completed', 'subtotal' => 246000,
            'shipping_fee' => 25000, 'total_amount' => 271000,
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'product_name' => 'Tên khi đặt hàng', 'sku' => $product->sku,
            'quantity' => 2, 'unit_price' => 123000, 'line_total' => 246000,
        ]);
        $admin = User::where('email', 'admin@minimart.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary->order_count === 1 && $summary->revenue === 271000.0)
            ->assertViewHas('itemSales', fn ($sales) => $sales->first()->product_name === 'Tên khi đặt hàng' && (float) $sales->sum('item_revenue') === 246000.0);
        $this->get(route('admin.reports.revenue'))->assertOk()
            ->assertViewHas('dailyRevenue', fn ($days) => $days->count() === 1 && (float) $days->sum('revenue') === 271000.0);
        $this->get(route('products.index', ['min_price' => 120000, 'max_price' => 125000, 'sort' => 'price_asc']))
            ->assertOk()->assertViewHas('products', fn ($products) => $products->contains('id', $product->id));
    }
}
