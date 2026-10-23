<?php
// fixtures/care_plans.php: SAMPLE DATA ONLY. Premium plans + monthly reminders (doc2 p9-10).
// Sir Paul: GET /api/plans/enrolled, GET /api/reminders?status=due, POST /api/reminders/{id}/confirm|change|skip,
// PATCH /api/plans/{id} {cadence, preferred_time, visit_mode, paused_from}. A reminder never creates a visit or charge on its own.
return [
    'reminders' => [
        ['id' => 'R-311', 'service' => 'mobile-mechanics', 'title' => 'Seasonal vehicle check-in', 'provider' => 'Mechanic Team A', 'text' => 'Winter is close. Would you like to arrange a pre-winter vehicle check with your mobile mechanic?', 'sent' => 'Oct 14 · 9:00 AM', 'delivery' => 'Delivered · email + in-app', 'state' => 'due'],
        ['id' => 'R-298', 'service' => 'snow-removal', 'title' => 'Winter storm plan check-in', 'provider' => 'Team A', 'text' => 'Season starts soon. Confirm your driveway and walkway preferences for this winter.', 'sent' => 'Oct 3 · 8:00 AM', 'delivery' => 'Read', 'state' => 'confirmed'],
        ['id' => 'R-276', 'service' => 'mobile-mechanics', 'title' => 'Vehicle check-in reminder', 'provider' => 'Mechanic Team A', 'text' => 'Season wrap-up check?', 'sent' => 'Sep 28 · 9:00 AM', 'delivery' => 'Delivered', 'state' => 'skipped'],
    ],
    'plans' => [
        ['service' => 'mobile-mechanics', 'name' => 'Seasonal vehicle check-in', 'provider' => 'Mechanic Team A', 'cadence' => 'Monthly', 'time' => 'Afternoon', 'mode' => 'Ask me to confirm each visit', 'fee' => 'No plan fee (sample)', 'visit' => 'Per-visit quote shown before you confirm', 'status' => 'active', 'next' => 'Due this month'],
        ['service' => 'snow-removal', 'name' => 'Winter storm plan', 'provider' => 'Team A', 'cadence' => 'Monthly check-in · storm visits by plan terms', 'time' => 'Early morning', 'mode' => 'Auto-schedule qualifying snow events (separately agreed)', 'fee' => 'Plan terms set by Nuahn', 'visit' => 'Per event, as agreed', 'status' => 'active', 'next' => 'Nov 1'],
    ],
];
