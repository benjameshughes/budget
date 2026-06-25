<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Blade directories to scan for Livewire feature and Alpine plugin usage.
    | By default scans your app's views and all Livewire components.
    |
    */

    'scan_paths' => [
        resource_path('views'),
        app_path('Livewire'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Include - Alpine Plugins
    |--------------------------------------------------------------------------
    |
    | Alpine plugins to always include regardless of scan results.
    | Useful for plugins used by admin panels (Filament) or packages
    | that aren't detected by the blade scanner.
    |
    | Available: collapse, focus, intersect, morph, persist, sort,
    |            resize, anchor, navigate, mask
    |
    | 'morph' and 'history' are always included (Livewire core deps).
    |
    */

    'force_plugins' => [
        'persist',
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Include - Features
    |--------------------------------------------------------------------------
    |
    | Livewire JS features to always include regardless of scan results.
    | Core features (listeners, scripts, js-eval, morph, dispatches)
    | are always included.
    |
    | Available: fileDownloads, fileUploads, queryString, laravelEcho,
    |            streaming, islands, navigate, slots, dataLoading,
    |            preserveScroll, wireIntersect, wireSort, jsModules,
    |            cssModules, disablingFormsDuringRequest, redirects,
    |            entangle, dataCurrent
    |
    */

    'force_features' => [],

    /*
    |--------------------------------------------------------------------------
    | Force Include - Directives
    |--------------------------------------------------------------------------
    |
    | Wire directives to always include regardless of scan results.
    | 'wire-wildcard' and 'wire-model' are always included (core).
    |
    | Available: wire-navigate, wire-confirm, wire-loading, wire-cloak,
    |            wire-transition, wire-current, wire-offline, wire-replace,
    |            wire-ignore, wire-dirty, wire-init, wire-poll, wire-show,
    |            wire-text, wire-bind
    |
    */

    'force_directives' => [],

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | Where the built slim JS file is written before injection.
    |
    */

    'output_path' => base_path('storage/slimwire'),

    /*
    |--------------------------------------------------------------------------
    | Bundles (Per-Route Code Splitting)
    |--------------------------------------------------------------------------
    |
    | Define named bundles to generate per-route JavaScript files.
    | Each bundle scans only the specified paths and serves its
    | slim build to matching routes. Leave empty for v1 behaviour
    | (single bundle for the whole app).
    |
    | WARNING: If you use wire:navigate between routes in different
    | bundles, ensure each bundle includes all features needed by
    | any page reachable via wire:navigate from that bundle's routes.
    |
    | 'bundles' => [
    |     'public' => [
    |         'paths' => [
    |             resource_path('views/livewire/public'),
    |             resource_path('views/layouts/public.blade.php'),
    |         ],
    |         'routes' => ['home', 'shop.*', 'product.*'],
    |     ],
    |     'admin' => [
    |         'paths' => [
    |             resource_path('views/livewire/admin'),
    |         ],
    |         'routes' => ['admin.*', 'filament.*'],
    |     ],
    | ],
    |
    */

    'bundles' => [
        'auth' => [
            'paths' => [
                resource_path('views/livewire/auth'),
                resource_path('views/components/layouts/auth.blade.php'),
            ],
            'routes' => ['login', 'register', 'password.*'],
        ],
        'app' => [
            'paths' => [
                resource_path('views/livewire'),
                resource_path('views/components/layouts/app.blade.php'),
                app_path('livewire'),
            ],
            'routes' => ['dashboard', 'transactions.*', 'settings.*'],
        ],
    ],
];
