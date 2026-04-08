<?php

namespace App\Providers;

use App\Events\LowStockReached;
use App\Events\OrderCompleted;
use App\Events\OrderRefunded;
use App\Events\OrderVoided;
use App\Listeners\CreateAutoReorderPurchaseOrder;
use App\Listeners\LogOrderActivity;
use App\Listeners\SendLowStockDatabaseNotification;
use App\Listeners\SendLowStockEmailNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEvents();
    }

    protected function registerEvents(): void
    {
        Event::listen(LowStockReached::class, SendLowStockDatabaseNotification::class);
        Event::listen(LowStockReached::class, SendLowStockEmailNotification::class);
        Event::listen(LowStockReached::class, CreateAutoReorderPurchaseOrder::class);
        Event::listen(OrderCompleted::class, LogOrderActivity::class);
        Event::listen(OrderVoided::class, LogOrderActivity::class);
        Event::listen(OrderRefunded::class, LogOrderActivity::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
