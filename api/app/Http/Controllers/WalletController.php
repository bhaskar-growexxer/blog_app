<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;
use RuntimeException;

class WalletController extends Controller
{
    protected WalletService $service;

    public function __construct(WalletService $service)
    {
        $this->service = $service;
    }

    public function transfer(Request $r)
    {
        $data = $r->validate([
            'from_wallet_id' => 'required|integer|exists:wallets,id',
            'to_wallet_id' => 'required|integer|exists:wallets,id',
            'amount_cents' => 'required|integer|min:1',
        ]);

        try {
            $tx = $this->service->transfer($data['from_wallet_id'], $data['to_wallet_id'], $data['amount_cents']);
            return response()->json(['ok' => true, 'transaction_id' => $tx->id]);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 400);
        }
    }
}