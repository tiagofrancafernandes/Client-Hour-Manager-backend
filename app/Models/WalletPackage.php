<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wallet_id
 * @property int $minutes
 * @property string $price
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage active()
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage query()
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage whereMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WalletPackage whereWalletId($value)
 * @mixin \Eloquent
 */
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
