<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'description',
        'is_default',
        'is_archived',
        'allow_client_purchases',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_archived' => 'boolean',
        'allow_client_purchases' => 'boolean',
    ];

    /**
     * Get the client that owns the wallet.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the transactions for the wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(HourTransaction::class);
    }

    /**
     * Get the timers for the wallet.
     */
    public function timers(): HasMany
    {
        return $this->hasMany(Timer::class);
    }

    /**
     * Get the packages for the wallet.
     */
    public function packages(): HasMany
    {
        return $this->hasMany(WalletPackage::class);
    }

    /**
     * Check if wallet is archived.
     */
    public function isArchived(): bool
    {
        return $this->is_archived;
    }

    /**
     * Check if wallet is default.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Check if client purchases are allowed.
     */
    public function allowsClientPurchases(): bool
    {
        return $this->allow_client_purchases;
    }
}
