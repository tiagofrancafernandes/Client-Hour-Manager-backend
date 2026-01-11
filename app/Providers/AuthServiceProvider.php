<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\Invoice;
use App\Models\Timer;
use App\Models\User;
use App\Models\Wallet;
use App\Policies\ClientPolicy;
use App\Policies\HourTransactionPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\TimerPolicy;
use App\Policies\UserPolicy;
use App\Policies\WalletPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Client::class => ClientPolicy::class,
        Wallet::class => WalletPolicy::class,
        Timer::class => TimerPolicy::class,
        Invoice::class => InvoicePolicy::class,
        HourTransaction::class => HourTransactionPolicy::class,
        User::class => UserPolicy::class,
        \Spatie\Permission\Models\Role::class => RolePolicy::class,
        \Spatie\Permission\Models\Permission::class => PermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
