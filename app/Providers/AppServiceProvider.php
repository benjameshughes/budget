<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ExpenseParserInterface;
use App\Contracts\FinancialAdvisorInterface;
use App\Events\Transaction\TransactionCreated;
use App\Listeners\GenerateFinancialFeedbackListener;
use App\Models\Bill;
use App\Models\BnplInstallment;
use App\Observers\BillObserver;
use App\Observers\BnplInstallmentObserver;
use App\Services\ExpenseParserService;
use App\Services\FinancialAdvisorService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExpenseParserInterface::class, ExpenseParserService::class);
        $this->app->bind(FinancialAdvisorInterface::class, FinancialAdvisorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Bill::observe(BillObserver::class);
        BnplInstallment::observe(BnplInstallmentObserver::class);

        Event::listen(
            TransactionCreated::class,
            GenerateFinancialFeedbackListener::class,
        );
    }
}
