<?php
// fixtures/plans.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/plans (subscription_plans table could back this).
return [
    'basic' => [
        'name' => 'Basic', 'price' => 'Pay per visit', 'tag' => 'Book on demand',
        'points' => ['Book any service when you need it', 'Pick a time slot; the provider accepts it', 'Live tracking & completion photos', 'Pay securely, released after you confirm'],
        'freq' => ['One-time'],
    ],
    'premium' => [
        'name' => 'Premium', 'price' => 'From CAD 49 / month', 'tag' => 'Recurring & priority',
        'points' => ['Recurring visits, e.g. bin wash every 2 weeks', 'Monthly mechanic check & diagnostics', 'Priority place in the snow-day queue', 'Same team each visit, 10% off add-ons'],
        'freq' => ['Recurring'],
    ],
];
