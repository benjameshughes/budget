<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ExpenseParserInterface;
use App\Models\Bill;
use App\Models\BnplInstallment;
use App\Observers\BillObserver;
use App\Observers\BnplInstallmentObserver;
use App\Services\ExpenseParserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExpenseParserInterface::class, ExpenseParserService::class);
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
