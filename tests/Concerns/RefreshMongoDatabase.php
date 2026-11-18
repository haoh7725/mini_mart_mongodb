<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait RefreshMongoDatabase
{
    public function setUpRefreshMongoDatabase(): void
    {
        $connection = DB::connection('mongodb');
        if (! app()->environment('testing') || ! str_ends_with($connection->getDatabaseName(), '_testing')) {
            throw new RuntimeException('Tests may only reset a MongoDB database ending in _testing.');
        }

        $connection->getDatabase()->drop();
        Artisan::call('migrate', ['--force' => true]);
    }
}
