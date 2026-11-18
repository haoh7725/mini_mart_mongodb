<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MongoQueries
{
    public static function aggregate(string $collection, array $pipeline): Collection
    {
        return collect(DB::connection('mongodb')->getCollection($collection)->aggregate($pipeline, [
            'typeMap' => ['root' => 'object', 'document' => 'array', 'array' => 'array'],
        ])->toArray());
    }

    public static function filter(Builder $query): array
    {
        return (clone $query)->toBase()->toMql()['find'][0];
    }

    public static function counts(Builder $query, string $field): Collection
    {
        return self::aggregate($query->getModel()->getTable(), [
            ['$match' => (object) self::filter($query)],
            ['$group' => ['_id' => '$'.$field, 'total' => ['$sum' => 1]]],
        ])->mapWithKeys(fn ($row) => [(string) $row->_id => $row->total]);
    }

    public static function paginatePrices(Builder $query, string $direction): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $ids = self::aggregate('products', [
            ['$match' => (object) self::filter($query)],
            ['$set' => ['_effective_price' => Product::effectivePriceExpression()]],
            ['$sort' => ['_effective_price' => $direction === 'price_asc' ? 1 : -1, '_id' => 1]],
            ['$skip' => ($page - 1) * 12], ['$limit' => 12], ['$project' => ['_id' => 1]],
        ])->map(fn ($row) => (string) $row->_id);
        $models = Product::with(['category', 'brand', 'inventoryLots', 'images'])->whereIn('id', $ids)->get()->keyBy('id');

        return (new LengthAwarePaginator($ids->map(fn ($id) => $models[$id]), (clone $query)->count(), 12, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]))->withQueryString();
    }

    public static function stockTotals(): Collection
    {
        return self::aggregate('inventory_lots', [
            ['$group' => ['_id' => '$product_id', 'available_stock' => ['$sum' => [
                '$subtract' => ['$quantity', ['$ifNull' => ['$reserved_quantity', 0]]],
            ]]]],
        ])->mapWithKeys(fn ($row) => [(string) $row->_id => $row->available_stock]);
    }

    public static function rescuedSavings(): float
    {
        return (float) (self::aggregate('rescue_campaigns', [
            ['$group' => ['_id' => null, 'total' => ['$sum' => ['$multiply' => [
                '$rescued_quantity', ['$subtract' => ['$original_price', '$rescue_price']],
            ]]]]],
        ])->first()->total ?? 0);
    }
}
