<?php
use Core\Env;

return [
    'youtube' => [
        'api_key'    => Env::get('YOUTUBE_API_KEY', ''),
        'base_url'   => 'https://www.googleapis.com/youtube/v3',
        'timeout'    => 8,   // seconds to wait for YouTube before giving up
        'cache_days' => 7,   // refresh a video's saved title/thumbnail after this many days
    ],
];
