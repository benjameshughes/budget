<?php

declare(strict_types=1);

namespace App\Queries;

use App\DataTransferObjects\Budget\BnplStatsDto;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class BnplQueries
{
    public function upcomingInstallments(User $user, int $limit = 0): Collection
    {
        $query = BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->with('purchase')
            ->orderBy('due_date');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function dueBetween(User $user, Carbon $from, Carbon $to): Collection
    {
        return BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->where(function ($query) use ($from, $to) {
                $query->where('due_date', '<', $from->toDateString())
                    ->orWhereBetween('due_date', [$from->toDateString(), $to->toDateString()]);
            })
            ->with('purchase')
            ->orderBy('due_date')
            ->get();
    }

    public function paginatedForUser(User $user, int $perPage = 25): LengthAwarePaginator
    {
        return BnplPurchase::query()
            ->forUser($user)
            ->with('installments')
            ->latest('purchase_date')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return BnplPurchase::query()
            ->forUser($user)
            ->with('installments')
            ->latest('purchase_date')
            ->get();
    }

    public function statsForUser(User $user): BnplStatsDto
    {
        $totalOutstanding = BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->sum('amount');

        $activePurchases = BnplPurchase::where('user_id', $user->id)
            ->whereHas('installments', fn ($q) => $q->where('is_paid', false))
            ->count();

        $totalPurchases = BnplPurchase::where('user_id', $user->id)->count();

        $overdueInstallments = BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->where('due_date', '<', now())
            ->count();

        $dueThisPeriod = BnplInstallment::with('purchase')
            ->where('user_id', $user->id)
            ->where('is_paid', false)
            ->where('due_date', '<=', now()->addWeeks(2))
            ->orderBy('due_date')
            ->get();

        return new BnplStatsDto(
            totalOutstanding: (float) $totalOutstanding,
            activePurchases: $activePurchases,
            totalPurchases: $totalPurchases,
            overdueInstallments: $overdueInstallments,
            dueThisPeriodAmount: (float) $dueThisPeriod->sum('amount'),
            dueThisPeriod: $dueThisPeriod,
        );
    }

    public function remainingBalance(User $user): float
    {
        return (float) BnplInstallment::where('user_id', $user->id)
            ->where('is_paid', false)
            ->sum('amount');
    }
}
