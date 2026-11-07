<?php

return [
    'api_key' => env('GEMINI_API_KEY', env('OPENAI_API_KEY')),
    'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 30),
];
