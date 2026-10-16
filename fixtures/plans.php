<?php
// fixtures/plans.php: SAMPLE DATA ONLY (doc2 p9). Sir Paul: replace with GET /api/plans?service= .
// Premium = provider reminders + saved preferences, not priority routing, discounts or automatic visits (doc2 p9 "No hidden assumptions").
return [
    'basic' => [
        'name' => 'Basic', 'price' => 'Pay per visit', 'tag' => 'Book whenever you need it', 'icon' => 'calendar3',
        'points' => ['One service visit at a time', 'Pick an available date and window', 'Track your team and job progress', 'Message, reschedule and view receipts', 'No ongoing care plan'],
        'freq' => ['One-time'],
    ],
    'premium' => [
        'name' => 'Premium', 'price' => 'Plan fee, if any, shown before activation', 'tag' => 'Stay on top of regular care', 'icon' => 'arrow-repeat',
        'points' => ['Monthly reminders from your provider', 'Save service and location preferences', 'Confirm, change or skip the next visit', 'Manage an ongoing care plan', 'Track every visit and message your team'],
        'freq' => ['Recurring'],
    ],
];
