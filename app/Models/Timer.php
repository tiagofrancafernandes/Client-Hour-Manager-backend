<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wallet_id
 * @property int|null $created_by
 * @property string $state
 * @property string|null $description
 * @property bool $is_hidden
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $paused_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property int $total_minutes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Client|null $creator
 * @property-read Wallet $wallet
 * @method static \Database\Factories\TimerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Timer hidden()
 * @method static \Illuminate\Database\Eloquent\Builder|Timer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Timer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Timer notHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|Timer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Timer visibleTo(\App\Models\User|\App\Models\Client $authenticatable)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer wherePausedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereTotalMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Timer whereWalletId($value)
 * @mixin \Eloquent
 */
class Timer extends Model
{
    use HasFactory;

    public const STATE_RUNNING = 'running';
    public const STATE_PAUSED = 'paused';
    public const STATE_STOPPED = 'stopped';
    public const STATE_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'created_by',
        'state',
        'description',
        'is_hidden',
        'started_at',
        'paused_at',
        'ended_at',
        'total_minutes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_hidden' => 'boolean',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_minutes' => 'integer',
    ];

    /**
     * Get the wallet that owns the timer.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the client who created the timer.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'created_by');
    }

    /**
     * Check if timer is running.
     */
    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
    }

    /**
     * Check if timer is paused.
     */
    public function isPaused(): bool
    {
        return $this->state === self::STATE_PAUSED;
    }

    /**
     * Check if timer is stopped.
     */
    public function isStopped(): bool
    {
        return $this->state === self::STATE_STOPPED;
    }

    /**
     * Check if timer is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->state === self::STATE_CANCELLED;
    }

    /**
     * Check if timer is hidden.
     */
    public function isHidden(): bool
    {
        return $this->is_hidden;
    }

    /**
     * Scope to get visible timers for a specific user or client.
     *
     * Rules:
     * - Admin (User with admin role) sees all timers
     * - Creator (Client) always sees their own timers (including hidden)
     * - Other clients see only non-hidden timers
     */
    public function scopeVisibleTo($query, User|Client $authenticatable)
    {
        // If it's a User with admin role, show all timers
        if ($authenticatable instanceof User && $authenticatable->hasRole('admin')) {
            return $query;
        }

        // For clients, show:
        // 1. All non-hidden timers
        // 2. Hidden timers created by the client themselves
        $clientId = $authenticatable instanceof Client
            ? $authenticatable->id
            : null;

        return $query->where(function ($q) use ($clientId) {
            $q->where('is_hidden', false);

            if ($clientId) {
                $q->orWhere('created_by', $clientId);
            }
        });
    }

    /**
     * Scope to get only non-hidden timers.
     */
    public function scopeNotHidden($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope to get only hidden timers.
     */
    public function scopeHidden($query)
    {
        return $query->where('is_hidden', true);
    }
}
