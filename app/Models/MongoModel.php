<?php

namespace App\Models;

use App\Support\DocumentDefaults;
use MongoDB\Laravel\Eloquent\Model;

abstract class MongoModel extends Model
{
    protected $connection = 'mongodb';

    public function __construct(array $attributes = [])
    {
        $this->attributes = array_replace(DocumentDefaults::for($this->getTable()), $this->attributes);
        parent::__construct($attributes);
    }

    public function setAttribute($key, $value)
    {
        if ($value !== null && in_array($key, DocumentDefaults::numericFields($this->getTable()), true)) {
            $value = in_array($key, ['sort_order', 'rating', 'points', 'usage_limit', 'used_count', 'discount_percent'], true)
                ? (int) $value : (float) $value;
        }
        if ($value !== null && (str_starts_with($key, 'is_') || str_starts_with($key, 'track_'))) {
            $value = (bool) $value;
        }

        return parent::setAttribute($key, $value);
    }

    protected function fromDecimal($value, $decimals)
    {
        return $value === null ? null : parent::fromDecimal($value, $decimals);
    }
}
