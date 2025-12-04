<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    href="{{route('home')}}"
                    logo=""
                    logo:dark=""
                    name="Money"
                    />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="space-y-1">
                <flux:sidebar.item icon="home" href="{{route('dashboard')}}" wire:current.exact class="transition-all duration-200 ease-in-out">Home</flux:sidebar.item>
                <flux:sidebar.item icon="chart-bar" href="{{route('analytics')}}" class="transition-all duration-200 ease-in-out">Analytics</flux:sidebar.item>
                <flux:sidebar.item icon="receipt-percent" href="{{route('transactions')}}" class="transition-all duration-200 ease-in-out">Transactions</flux:sidebar.item>
                <flux:sidebar.item icon="banknotes" href="{{route('bnpl')}}" class="transition-all duration-200 ease-in-out">BNPL</flux:sidebar.item>
                <flux:sidebar.item icon="credit-card" href="{{route('credit-cards')}}" class="transition-all duration-200 ease-in-out">Credit Cards</flux:sidebar.item>
                <flux:sidebar.item icon="document-text" href="{{route('bills')}}" class="transition-all duration-200 ease-in-out">Bills</flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav class="space-y-1">
                <flux:sidebar.item icon="cog-6-tooth" href="{{route('profile.edit')}}" class="transition-all duration-200 ease-in-out">Settings</flux:sidebar.item>
                <flux:sidebar.item icon="information-circle" href="#" class="transition-all duration-200 ease-in-out">Help</flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile name="{{ auth()->user()->name }}" />
                <flux:menu>
                    <flux:menu.item disabled>{{ auth()->user()->email }}</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{route('logout')}}">
                        @csrf
                        <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:dropdown position="top" align="start">
                <flux:profile name="{{ auth()->user()->name }}" />
                <flux:menu>
                    <flux:menu.item disabled>{{ auth()->user()->email }}</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{route('logout')}}">
                        @csrf
                        <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
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
