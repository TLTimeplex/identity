<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hydra Admin API
    |--------------------------------------------------------------------------
    |
    | This URL must only be reachable from trusted application services.
    | Never expose the Hydra Admin API publicly.
    |
    */

    'admin_url' => env('HYDRA_ADMIN_URL', 'http://localhost:4445'),

    'public_url' => env('HYDRA_PUBLIC_URL', 'http://localhost:4444'),

    'timeout' => (int) env('HYDRA_TIMEOUT', 5),

    'connect_timeout' => (int) env('HYDRA_CONNECT_TIMEOUT', 2),
];
