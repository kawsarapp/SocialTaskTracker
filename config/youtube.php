<?php

return [
    'client_id'     => env('YOUTUBE_CLIENT_ID'),
    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
    'redirect_uri'  => env('YOUTUBE_REDIRECT_URI'),
    'scopes'        => [
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube',
    ],
];
