<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wallet_id
 * @property string $status
 * @property int $minutes
 * @property string $price_per_hour
 * @property string $total_amount
 * @property string|null $client_message
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Wallet $wallet
 * @method static \Database\Factories\InvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereClientMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice wherePricePerHour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereWalletId($value)
 * @mixin \Eloquent
 */
class Invoice extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'status',
        'minutes',
        'price_per_hour',
        'total_amount',
        'client_message',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'minutes' => 'integer',
        'price_per_hour' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns the invoice.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Check if invoice is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
