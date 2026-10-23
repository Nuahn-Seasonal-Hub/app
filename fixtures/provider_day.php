<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// fixtures/provider_day.php: SAMPLE DATA ONLY. Contractor dashboard "Good morning, Jordan" (independent contractor model).
// Sir Paul: GET /api/contractors/{id}/day?date= (accepted jobs with approved scope, access, price, window, payment state),
// POST /api/jobs/{id}/events {arrived|start|pause|delay|complete}, POST /api/jobs/{id}/media {kind: before|after} (queued offline; "Pending sync"),
// POST /api/jobs/{id}/completion (rejects with 422 unless at least one before and one after photo exist),
// POST /api/jobs/{id}/extra-work, GET /api/contractors/{id}/plan-visits, GET /api/contractors/{id}/payouts.
// Only the two live services appear: Winter Snow Removal and Mobile Mechanics.
return [
    'contractor' => 'c-1001', 'date' => 'Fri, Oct 23, 2026', 'time' => '7:12 AM',
    'stats' => [['megaphone', 4, 'Open jobs near you', 'Accept or counter-bid'], ['clipboard-data', 2, 'Active jobs', 'Accepted and paid into escrow'], ['hourglass-split', 1, 'Awaiting approval', 'Client is reviewing your photos']],
    'jobs' => [
        '1042' => ['id' => 'NSH-1042', 'service' => 'Snow Removal', 'slug' => 'snow-removal', 'window' => '8 – 11 AM', 'where' => '123 Maple Grove Dr · Edmonton SW', 'status' => 'in_progress', 'payment' => 'authorized', 'price' => 48,
            'scope' => 'Standard driveway + front walkway', 'access' => 'Keep side gate closed', 'plan' => 'One-off', 'quote' => '$48 accepted offer', 'property' => 'Standard driveway (1–2 cars)',
            'progress' => ['On the way', 'Arrived', 'Start work', 'Report delay'], 'at' => 2,
            'checklist' => [['Driveway cleared', true], ['Front walkway cleared', true], ['Steps cleared and salted', false]]],
        '1045' => ['id' => 'SNOW-1045', 'service' => 'Snow Removal', 'slug' => 'snow-removal', 'window' => 'Tomorrow · 9 – 11 AM', 'where' => '42 Aspen Glen Pl SW · Edmonton', 'status' => 'confirmed', 'payment' => 'authorized', 'price' => 45,
            'scope' => 'Seasonal plan visit · 5 cm trigger', 'access' => 'Double driveway', 'plan' => 'Seasonal plan', 'quote' => '$45 per visit', 'property' => 'Large driveway (3–4 cars)',
            'progress' => ['On the way', 'Arrived', 'Start work', 'Report delay'], 'at' => -1,
            'checklist' => [['Driveway cleared', false], ['Walkway cleared', false], ['Steps cleared and salted', false]]],
        '2051' => ['id' => 'TASK-2051', 'service' => 'Snow Removal', 'slug' => 'snow-removal', 'window' => 'On demand', 'where' => 'Maple Grove · Edmonton SW', 'status' => 'open_for_bids', 'payment' => 'unpaid', 'price' => 48,
            'scope' => 'Standard driveway + front walkway, steps and ice melt', 'access' => 'Not shared until you are confirmed', 'plan' => 'One-off', 'quote' => 'Your offer: $48 · client target $45', 'property' => 'Standard driveway (1–2 cars)',
            'progress' => ['On the way', 'Arrived', 'Start work', 'Report delay'], 'at' => -1,
            'checklist' => [['Driveway cleared', false], ['Walkway cleared', false], ['Steps cleared and salted', false]]],
    ],
    'plan_visits' => [
        ['Alice C.', 'Seasonal plan · 5 cm trigger', 'Next: snow event Nov 2', 'scheduled'], ['R. Patel', 'Seasonal plan · 5 cm trigger', 'Visit done · awaiting approval', 'replied'], ['D. Martin', 'Seasonal plan · 5 cm trigger', 'Skipped: under 5 cm', 'skipped'],
    ],
    'week' => [['Mon', '7 AM – 5 PM'], ['Tue', '7 AM – 5 PM'], ['Wed', 'Off'], ['Thu', '7 AM – 5 PM'], ['Fri', '7 AM – 3 PM'], ['Sat', 'Storm on-call'], ['Sun', 'Off']],
];
