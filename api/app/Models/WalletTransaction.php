<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = ['from_wallet_id', 'to_wallet_id', 'amount', 'type', 'meta'];

    protected $casts = [
        'amount' => 'integer',
        'meta' => 'array',
    ];
}