<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Exception\RuntimeException;
use Throwable;

class DocumentTransaction
{
    // MongoDB has no nested transactions. Inner services join the caller's transaction.
    public static function run(Closure $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction($callback, attempts: 1);
            } catch (Throwable $exception) {
                // Clear callbacks between retries so a failed attempt never sends notifications.
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                if (! ($exception instanceof RuntimeException)
                    || ! $exception->hasErrorLabel('TransientTransactionError') || $attempt === 4) {
                    throw $exception;
                }
                usleep(25000 * ($attempt + 1));
            }
        }

        throw new \LogicException('Transaction retry limit exceeded.');
    }
}
