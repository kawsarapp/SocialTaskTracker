<?php

return [
    'key'        => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
    'secret'     => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
    'bucket'     => env('CLOUDFLARE_R2_BUCKET', 'video-saas-bucket'),
    'endpoint'   => env('CLOUDFLARE_R2_ENDPOINT'),
    'public_url' => env('CLOUDFLARE_R2_PUBLIC_URL'),
    'account_id' => env('CLOUDFLARE_R2_ACCOUNT_ID'),
];
