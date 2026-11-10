<?php

return [
    'api_key' => env('GROQ_API_KEY', ''),
    'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    'timeout' => (int) env('GROQ_TIMEOUT', 30),
];
