<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use RuntimeException;

class WalletService
{
    protected int $maxRetries = 5;

    /**
     * Transfer amount (in cents) from one wallet to another.
     */
    public function transfer(int $fromWalletId, int $toWalletId, int $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be positive.');
        }

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return DB::transaction(function () use ($fromWalletId, $toWalletId, $amount) {

                    // Explicit isolation level (important for ACID guarantees)
                    DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

                    // Lock wallets in consistent order to avoid deadlocks
                    $orderedIds = [$fromWalletId, $toWalletId];
                    sort($orderedIds);

                    $wallets = Wallet::whereIn('id', $orderedIds)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    $from = $wallets[$fromWalletId] ?? null;
                    $to   = $wallets[$toWalletId] ?? null;

                    if (! $from || ! $to) {
                        throw new RuntimeException('Wallet not found.');
                    }

                    if ($from->balance < $amount) {
                        throw new RuntimeException('Insufficient funds.');
                    }

                    // Apply balance changes
                    $from->balance -= $amount;
                    $to->balance   += $amount;

                    $from->save();
                    $to->save();

                    return WalletTransaction::create([
                        'from_wallet_id' => $from->id,
                        'to_wallet_id'   => $to->id,
                        'amount'         => $amount,
                        'type'           => 'transfer',
                    ]);
                }, 3); // Laravel-level transaction retry safeguard
            } catch (QueryException $e) {
                if (! $this->isDeadlock($e) || $attempt === $this->maxRetries) {
                    throw $e;
                }

                // Exponential backoff with jitter
                usleep((int) (100000 * $attempt + random_int(0, 50000)));
            }
        }

        throw new RuntimeException('Transfer failed after maximum retries.');
    }

    /**
     * Detect database deadlock or lock timeout.
     */
    protected function isDeadlock(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $errorCode = $e->errorInfo[1] ?? null;

        // MySQL
        if ($sqlState === '40001' || in_array($errorCode, [1213, 1205], true)) {
            return true;
        }

        // PostgreSQL
        if ($sqlState === '40P01') {
            return true;
        }

        return false;
    }
}
