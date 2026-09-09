<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI provider
    |--------------------------------------------------------------------------
    |
    | - ollama : local (http://127.0.0.1:11434/v1), no paid key
    | - openai : OpenAI-compatible cloud (needs AI_API_KEY)
    |
    */

    'enabled' => (bool) env('AI_ENABLED', true),

    'provider' => env('AI_PROVIDER', 'ollama'),

    'api_key' => env('AI_API_KEY', 'ollama'),

    'base_url' => rtrim(env('AI_BASE_URL', 'http://127.0.0.1:11434/v1'), '/'),

    'model' => env('AI_MODEL', 'qwen2.5'),

    'embedding_model' => env('AI_EMBEDDING_MODEL', 'nomic-embed-text'),

    'timeout' => (int) env('AI_TIMEOUT', 60),

    'rag' => [
        'enabled' => (bool) env('AI_RAG_ENABLED', true),
        'top_k' => (int) env('AI_RAG_TOP_K', 3),
        'min_score' => (float) env('AI_RAG_MIN_SCORE', 0.28),
    ],

];
