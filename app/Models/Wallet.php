<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $client_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_default
 * @property-read bool $is_archived
 * @property bool $allow_client_purchases
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Client $client
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WalletPackage> $packages
 * @property-read int|null $packages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Timer> $timers
 * @property-read int|null $timers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HourTransaction> $transactions
 * @property-read int|null $transactions_count
 * @method static \Database\Factories\WalletFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet query()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereAllowClientPurchases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereIsArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        'archived_at',
        'allow_client_purchases',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'archived_at' => 'datetime',
        'allow_client_purchases' => 'boolean',
    ];

    protected $appends = [
        'is_archived',
    ];

    /**
     * Get whether the wallet is archived.
     */
    public function getIsArchivedAttribute(): bool
    {
        return filled($this->archived_at);
    }

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
        return $this->getIsArchivedAttribute();
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
