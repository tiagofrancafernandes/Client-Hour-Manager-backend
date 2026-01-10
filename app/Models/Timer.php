<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
