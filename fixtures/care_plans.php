<?php
// fixtures/care_plans.php: SAMPLE DATA ONLY. Premium plans + monthly reminders (doc2 p9-10).
// Sir Paul: GET /api/plans/enrolled, GET /api/reminders?status=due, POST /api/reminders/{id}/confirm|change|skip,
// PATCH /api/plans/{id} {cadence, preferred_time, visit_mode, paused_from}. A reminder never creates a visit or charge on its own.
return [
    'reminders' => [
        ['id' => 'R-311', 'service' => 'car-wash', 'title' => 'Monthly car-care reminder', 'provider' => 'Nuahn Detail Team', 'text' => 'Your next reminder is due this month. Would you like to arrange your next car wash?', 'sent' => 'Oct 14 · 9:00 AM', 'delivery' => 'Delivered · email + in-app', 'state' => 'due'],
        ['id' => 'R-298', 'service' => 'garbage-bin-cleaning', 'title' => 'Bin clean reminder', 'provider' => 'Team B', 'text' => 'Ready for your next bin clean? Your collection day is Tuesday.', 'sent' => 'Oct 3 · 8:00 AM', 'delivery' => 'Read', 'state' => 'confirmed'],
        ['id' => 'R-276', 'service' => 'lawn-mowing', 'title' => 'Lawn visit reminder', 'provider' => 'Lawn Team D', 'text' => 'Season wrap-up visit?', 'sent' => 'Sep 28 · 9:00 AM', 'delivery' => 'Delivered', 'state' => 'skipped'],
    ],
    'plans' => [
        ['service' => 'car-wash', 'name' => 'Monthly car-care reminder', 'provider' => 'Nuahn Detail Team', 'cadence' => 'Monthly', 'time' => 'Afternoon', 'mode' => 'Ask me to confirm each visit', 'fee' => 'No plan fee (sample)', 'visit' => 'Per-visit price shown before you confirm', 'status' => 'active', 'next' => 'Due this month'],
        ['service' => 'garbage-bin-cleaning', 'name' => 'Bin clean reminders', 'provider' => 'Team B', 'cadence' => 'Monthly', 'time' => 'Morning', 'mode' => 'Ask me to confirm each visit', 'fee' => 'No plan fee (sample)', 'visit' => 'CAD 30.00 per visit (sample)', 'status' => 'active', 'next' => 'Nov 3'],
        ['service' => 'snow-removal', 'name' => 'Winter storm plan', 'provider' => 'Team A', 'cadence' => 'Monthly check-in · storm visits by plan terms', 'time' => 'Early morning', 'mode' => 'Auto-schedule qualifying snow events (separately agreed)', 'fee' => 'Plan terms set by Nuahn', 'visit' => 'Per event, as agreed', 'status' => 'active', 'next' => 'Nov 1'],
        ['service' => 'lawn-mowing', 'name' => 'Lawn visit reminders', 'provider' => 'Lawn Team D', 'cadence' => 'Monthly', 'time' => 'Morning', 'mode' => 'Ask me to confirm each visit', 'fee' => 'No plan fee (sample)', 'visit' => 'CAD 40.00 per cut (sample)', 'status' => 'paused', 'next' => 'Paused from Oct 1'],
    ],
];
