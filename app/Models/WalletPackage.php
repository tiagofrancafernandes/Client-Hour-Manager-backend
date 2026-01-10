<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'minutes',
        'price',
        'is_active',
    ];

    protected $casts = [
        'minutes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
