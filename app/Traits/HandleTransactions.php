<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandleTransactions
{
    /**
     * Wrap a callback in a database transaction with error handling.
     *
     * @param  int|null  $attempts
     * @return mixed
     *
     * @throws Throwable
     */
    public function transact(callable $callback, int $attempts = 1)
    {
        try {
            return DB::transaction($callback, $attempts);
        } catch (Throwable $e) {
            Log::error('[Transaction Failed] '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
