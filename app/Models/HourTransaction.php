<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wallet_id
 * @property string $type
 * @property int $minutes
 * @property string|null $description
 * @property string|null $internal_note
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Wallet $wallet
 * @method static \Database\Factories\HourTransactionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereInternalNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereOccurredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HourTransaction whereWalletId($value)
 * @mixin \Eloquent
 */
class HourTransaction extends Model
{
    use HasFactory;

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'type',
        'minutes',
        'description',
        'internal_note',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'minutes' => 'integer',
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Check if transaction is a credit.
     */
    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT || $this->type === self::TYPE_TRANSFER_IN;
    }

    /**
     * Check if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT || $this->type === self::TYPE_TRANSFER_OUT;
    }

    /**
     * Get signed minutes (positive for credit, negative for debit).
     */
    public function getSignedMinutes(): int
    {
        return $this->isCredit() ? $this->minutes : -$this->minutes;
    }
}
