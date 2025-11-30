<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Bill;
use App\Models\BnplInstallment;
use App\Observers\BillObserver;
use App\Observers\BnplInstallmentObserver;
use Illuminate\Support\ServiceProvider;

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
        Bill::observe(BillObserver::class);
        BnplInstallment::observe(BnplInstallmentObserver::class);
    }
}
