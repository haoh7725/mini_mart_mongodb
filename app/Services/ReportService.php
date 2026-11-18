<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Support\MongoQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

class ReportService
{
    public function forMonth(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $start->copy()->addMonth();
        $orders = Order::where('status', 'completed')->where('created_at', '>=', $start)->where('created_at', '<', $end);
        $summary = (object) ['order_count' => (clone $orders)->count(), 'revenue' => (float) (clone $orders)->sum('total_amount')];
        $dailyRevenue = MongoQueries::aggregate('orders', [
            ['$match' => ['status' => 'completed', 'created_at' => ['$gte' => new UTCDateTime($start), '$lt' => new UTCDateTime($end)]]],
            ['$group' => ['_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$created_at', 'timezone' => config('app.timezone')]], 'order_count' => ['$sum' => 1], 'revenue' => ['$sum' => '$total_amount']]],
            ['$project' => ['_id' => 0, 'day' => '$_id', 'order_count' => 1, 'revenue' => 1]], ['$sort' => ['day' => 1]],
        ]);

        // Normalize IDs inside lookup for both imported SQL IDs and ObjectIds.
        $sales = MongoQueries::aggregate('order_items', [
            ['$lookup' => ['from' => 'orders', 'let' => ['orderId' => '$order_id'], 'pipeline' => [
                ['$match' => ['$expr' => ['$eq' => [['$toString' => '$_id'], '$$orderId']], 'status' => 'completed', 'created_at' => ['$gte' => new UTCDateTime($start), '$lt' => new UTCDateTime($end)]]],
            ], 'as' => 'order']],
            ['$unwind' => '$order'],
            ['$group' => [
                '_id' => ['product_id' => '$product_id', 'product_name' => '$product_name', 'sku' => '$sku'],
                'quantity_sold' => ['$sum' => '$quantity'], 'item_revenue' => ['$sum' => '$line_total'],
                'order_ids' => ['$addToSet' => '$order_id'], 'item_ids' => ['$addToSet' => ['$toString' => '$_id']],
            ]],
            ['$sort' => ['quantity_sold' => -1]],
        ]);
        $products = Product::withTrashed()->with('category')->whereIn('id', $sales->map(fn ($sale) => $sale->_id['product_id']))->get()->keyBy('id');
        $stock = MongoQueries::stockTotals();
        $units = DB::table('units')->get()->mapWithKeys(fn ($unit) => [(string) $unit->id => $unit->symbol]);
        $reviewedIds = DB::table('reviews')->whereIn('order_item_id', $sales->flatMap(fn ($sale) => $sale->item_ids))->pluck('order_item_id')->map(fn ($id) => (string) $id)->flip();

        $itemSales = $sales->map(function ($sale) use ($products, $stock, $units, $reviewedIds) {
            $id = (string) ($sale->_id['product_id'] ?? '');
            $product = $products->get($id);
            $reviewed = collect($sale->item_ids)->filter(fn ($item) => $reviewedIds->has($item))->count();

            return (object) [
                'product_id' => $id, 'product_name' => $sale->_id['product_name'], 'sku' => $sale->_id['sku'],
                'quantity_sold' => $sale->quantity_sold, 'item_revenue' => $sale->item_revenue,
                'order_count' => count($sale->order_ids), 'sold_lines' => count($sale->item_ids), 'reviewed_lines' => $reviewed,
                'response_rate' => count($sale->item_ids) ? $reviewed * 100 / count($sale->item_ids) : 0,
                'available_stock' => $stock->get($id, 0), 'unit_symbol' => $units->get($product?->unit_id, ''),
                'category_id' => $product?->category_id, 'category_name' => $product?->category?->name ?? 'Chưa phân loại',
            ];
        });
        $categoryRevenue = $itemSales->groupBy('category_id')->map(fn ($items) => (object) [
            'category_name' => $items->first()->category_name, 'quantity_sold' => $items->sum('quantity_sold'), 'item_revenue' => $items->sum('item_revenue'),
        ])->sortByDesc('item_revenue')->values();

        $stockProducts = MongoQueries::aggregate('products', [
            ['$match' => ['is_active' => true, 'deleted_at' => null]],
            ['$lookup' => ['from' => 'inventory_lots', 'let' => ['productId' => ['$toString' => '$_id']], 'pipeline' => [
                ['$match' => ['$expr' => ['$eq' => ['$product_id', '$$productId']]]],
                ['$group' => ['_id' => null, 'total' => ['$sum' => ['$subtract' => ['$quantity', ['$ifNull' => ['$reserved_quantity', 0]]]]]]],
            ], 'as' => 'stock']],
            ['$set' => ['available_stock' => ['$ifNull' => [['$arrayElemAt' => ['$stock.total', 0]], 0]], 'id' => ['$toString' => '$_id']]],
            ['$sort' => ['available_stock' => -1, 'name' => 1]], ['$limit' => 20],
            ['$project' => ['_id' => 0, 'id' => 1, 'name' => 1, 'sku' => 1, 'unit_id' => 1, 'available_stock' => 1]],
        ]);

        return compact('summary', 'dailyRevenue', 'itemSales', 'categoryRevenue', 'stockProducts', 'units');
    }

    public function monthlyRevenue(int $months): Collection
    {
        return collect(range($months - 1, 0))->map(function ($monthsAgo) {
            $period = now()->startOfMonth()->subMonths($monthsAgo);

            return ['label' => $period->format('m/Y'), 'revenue' => (float) Order::where('status', 'completed')->where('created_at', '>=', $period)->where('created_at', '<', $period->copy()->addMonth())->sum('total_amount')];
        });
    }
}
