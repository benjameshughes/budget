<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-100 antialiased dark:bg-slate-950">
        <flux:sidebar sticky collapsible class="lg:m-4 lg:rounded-2xl shadow-2xl bg-zinc-950 p-2">
            <flux:sidebar.header>
                <flux:sidebar.brand href="{{route('home')}}" name="Money" class="text-white">
                    <x-slot name="logo" class="size-6 rounded bg-violet-600 text-white">
                        <flux:icon name="hand-coins" variant="micro" />
                    </x-slot>
                </flux:sidebar.brand>
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2 text-zinc-400 hover:text-white" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="space-y-4">
                <flux:sidebar.group heading="Overview" class="text-zinc-400 uppercase tracking-widest text-[10px]">
                    <flux:sidebar.item icon="home" href="{{route('dashboard')}}" wire:current.exact class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Home</flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" href="{{route('analytics')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Analytics</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Money" class="text-zinc-400 uppercase tracking-widest text-[10px]">
                    <flux:sidebar.item icon="receipt-percent" href="{{route('transactions')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Transactions</flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" href="{{route('bnpl')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">BNPL</flux:sidebar.item>
                    <flux:sidebar.item icon="credit-card" href="{{route('credit-cards')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Credit Cards</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Planning" class="text-zinc-400 uppercase tracking-widest text-[10px]">
                    <flux:sidebar.item icon="document-text" href="{{route('bills')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Bills</flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-trending-down" href="{{route('debts')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Debt Snowball</flux:sidebar.item>
                    <flux:sidebar.item icon="building-library" href="{{route('savings')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Savings</flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" href="{{route('penny-challenge')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">1p Challenge</flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" href="{{route('calendar')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Calendar</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Automate" class="text-zinc-400 uppercase tracking-widest text-[10px]">
                    <flux:sidebar.item icon="bolt" href="{{route('automations')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">
                        Automations
                        @auth
                            @php $todayRuns = \App\Models\AutomationRuleLog::where('user_id', auth()->id())->whereDate('ran_at', today())->count(); @endphp
                            @if($todayRuns > 0)
                                <flux:badge size="sm" color="violet" class="ml-auto">{{ $todayRuns }}</flux:badge>
                            @endif
                        @endauth
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav class="space-y-0.5">
                <flux:sidebar.item icon="cog-6-tooth" href="{{route('profile.edit')}}" class="text-white hover:bg-white/10 [&[data-current]]:bg-violet-700 [&[data-current]]:text-white [&[data-current]]:ring-1 [&[data-current]]:ring-violet-900 transition-all duration-200 ease-in-out">Settings</flux:sidebar.item>
                <form method="POST" action="{{route('logout')}}">
                    @csrf
                    <flux:sidebar.item type="submit" icon="arrow-right-start-on-rectangle" class="text-zinc-400 hover:text-white hover:bg-white/10 transition-all duration-200 ease-in-out">Logout</flux:sidebar.item>
                </form>
            </flux:sidebar.nav>
        </flux:sidebar>

        <flux:header class="lg:hidden mx-3 mt-3 rounded-2xl bg-white shadow-sm dark:bg-zinc-900 border-0">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <form method="POST" action="{{route('logout')}}">
                @csrf
                <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle" />
            </form>
        </flux:header>

        {{ $slot }}

        {{-- Global Quick Input (⌘K) --}}
        @auth
            <livewire:quick-input />
        @endauth

        @persist('toast')
            <flux:toast />
        @endpersist
        @fluxScripts
    </body>
</html>
