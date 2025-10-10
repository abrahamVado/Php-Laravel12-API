<?php

return [
    //1.- Define the route patterns that should include CORS headers for SPA interactions.
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    //2.- Allow every HTTP method so that preflight requests succeed without manual curation.
    'allowed_methods' => ['*'],

    //3.- Restrict origins to the configured frontend URL to support credentials instead of wildcards.
    'allowed_origins' => [
        env('APP_FRONTEND_URL', 'http://localhost:3000'),
    ],

    //4.- Keep pattern-based origins empty to avoid inadvertently permitting wildcards.
    'allowed_origins_patterns' => [],

    //5.- Permit any headers so the browser can send authentication information freely.
    'allowed_headers' => ['*'],

    //6.- Expose no additional headers by default to comply with credentialed request limitations.
    'exposed_headers' => [],

    //7.- Disable caching of preflight responses to honor dynamic configuration changes promptly.
    'max_age' => 0,

    //8.- Enable credential support so cookies and authorization headers are accepted cross-origin.
    'supports_credentials' => true,
];
