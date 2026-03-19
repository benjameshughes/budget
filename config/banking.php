<?php

declare(strict_types=1);

return [

    'monzo' => [
        'client_id' => env('MONZO_CLIENT_ID'),
        'client_secret' => env('MONZO_CLIENT_SECRET'),
        'redirect_uri' => env('MONZO_REDIRECT_URI'),
        'api_base_url' => 'https://api.monzo.com',
    ],

    'starling' => [
        'api_base_url' => 'https://api.starlingbank.com',
    ],

];
