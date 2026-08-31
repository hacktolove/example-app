<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Wave Diameter Gateway
    |--------------------------------------------------------------------------
    |
    | Connection details for the external Wave Diameter Gateway. Every
    | outbound request is a POST to {base_url}/DiameterEventCharging
    | authenticated with HTTP Basic Auth using the credentials below.
    |
    */

    'base_url' => env('WAVE_BASE_URL'),

    'username' => env('WAVE_USERNAME'),

    'password' => env('WAVE_PASSWORD'),

    'timeout' => (int) env('WAVE_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Origin Host Name
    |--------------------------------------------------------------------------
    |
    | Sent as `originHostName` on every Wave request to identify this
    | application to the gateway. Defaults to the app name; override only
    | if Wave requires a distinct identifier.
    |
    */

    'origin_host_name' => env('WAVE_ORIGIN_HOST_NAME', config('app.name')),

];
