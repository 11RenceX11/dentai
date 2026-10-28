<?php

return [
    // Dashboard → Developers → API keys. Use sk_test_... while testing, sk_live_... in production.
    'secret_key' => env('PAYMONGO_SECRET_KEY'),

    // Dashboard → Developers → Webhooks → your endpoint → signing secret.
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),

    'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com'),

    // Must also be switched on in your PayMongo dashboard (Settings → Payment Methods).
    'methods' => array_values(array_filter(array_map('trim', explode(',', env('PAYMONGO_METHODS', 'gcash,paymaya,card,qrph'))))),
];
