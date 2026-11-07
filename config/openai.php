<?php

return [
    'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1'),
    'api_key' => env('OPENAI_API_KEY', ''),
    'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
    'timeout' => (int) env('OPENAI_TIMEOUT', 30),
];
