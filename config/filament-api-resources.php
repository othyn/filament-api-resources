<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your API. This will be prepended to all endpoint calls.
    |
    */
    'base_url' => env('FILAMENT_API_BASE_URL', 'https://api.example.com'),

    /*
    |--------------------------------------------------------------------------
    | Default Headers
    |--------------------------------------------------------------------------
    |
    | Default headers to be sent with every API request. You can override
    | these on a per-request basis if needed.
    |
    */
    'default_headers' => [
        'Authorization' => 'Bearer '.env('FILAMENT_API_TOKEN', ''),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Query Parameters
    |--------------------------------------------------------------------------
    |
    | Configure the query parameter names sent to your API for pagination.
    | These match Laravel's default pagination parameter names.
    |
    */
    'pagination_params' => [
        'page' => 'page',           // Query parameter for current page number
        'per_page' => 'per_page',   // Query parameter for items per page
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Default cache settings for API responses.
    |
    */
    'cache' => [
        'default_ttl' => env('FILAMENT_API_CACHE_TTL', 300), // 5 minutes
        'prefix' => env('FILAMENT_API_CACHE_PREFIX', 'filament_api_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Structure
    |--------------------------------------------------------------------------
    |
    | Configure the expected structure of your API responses. These can be
    | overridden on a per-model basis if your API has different structures
    | for different endpoints.
    |
    */
    'response_structure' => [
        'total_key' => 'data.total',    // Key that contains the total count (supports dot notation)
        'results_key' => 'data.data',   // Key that contains the array of results (supports dot notation)
        'meta_key' => 'data',           // Key that contains metadata
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the HTTP client used to make API requests.
    |
    */
    'http' => [
        'timeout' => env('FILAMENT_API_TIMEOUT', 30),
        'retry_attempts' => env('FILAMENT_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('FILAMENT_API_RETRY_DELAY', 100), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for API operations.
    |
    */
    'logging' => [
        'enabled' => env('FILAMENT_API_LOGGING_ENABLED', true),
        'channel' => env('FILAMENT_API_LOGGING_CHANNEL', 'default'),
        'level' => env('FILAMENT_API_LOGGING_LEVEL', 'error'),
        'include_request_data' => env('FILAMENT_API_LOGGING_INCLUDE_REQUEST_DATA', true),
        'include_response_data' => env('FILAMENT_API_LOGGING_INCLUDE_RESPONSE_DATA', false),
    ],
];
