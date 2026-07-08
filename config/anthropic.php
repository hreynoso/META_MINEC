<?php

return [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
    'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
];
