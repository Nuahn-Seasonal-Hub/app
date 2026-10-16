<?php
// fixtures/ops.php: SAMPLE DATA ONLY. Admin "Operations overview" (doc1 p5, doc2 p20).
// Sir Paul: GET /api/ops/summary?area=&date=, GET /api/ops/queue?queue=&service=&area=&status=&q=,
// GET /api/teams/availability, POST /api/bookings/{id}/assign {team_id}, weather review endpoints.
// queue keys: dispatch | reschedule | snow | equipment | reminders | issues | quotes | payments (doc2 p20 "add the missing queues").
return [
    'areas' => ['Edmonton', 'St. Albert', 'Sherwood Park'],
    'kpis' => [
        ['calendar3', 28, 'Jobs today', 'teal'], ['people-fill', 4, 'Unassigned', 'orange'], ['clock', 3, 'Delayed', 'red'], ['file-text', 2, 'Quotes pending', 'blue'],
    ],
    'queues' => [
        'dispatch' => ['label' => 'Dispatch', 'rows' => [
            ['#1042', 'Snow Removal', '8 – 11 AM', 'in_progress', 'Team A', 'Open'],
            ['#1043', 'Garbage Can Cleaning', '11 AM – 1 PM', 'unassigned', '—', 'Assign'],
            ['#1044', 'Mobile Mechanics', '1 – 3 PM', 'quote_pending', 'Team B', 'Review'],
            ['#1045', 'Mobile Car Wash', '2 – 4 PM', 'weather_review', 'Team C', 'Review'],
        ]],
        'reschedule' => ['label' => 'Reschedule requests', 'rows' => [
            ['#SNOW-1045', 'Winter Snow Removal', 'Today 9–11 AM → 2–4 PM', 'change_requested', 'Team A', 'Review'],
            ['#MECH-1042', 'Mobile Mechanics', 'Tue 10–12 → Wed 1–3 PM', 'confirmed', 'Team A', 'Open'],
        ]],
        'snow' => ['label' => 'Snow events', 'rows' => [
            ['SE-1103', 'Snow event · Edmonton SW', '14 plan visits · 6 one-off', 'confirmed', '3 teams', 'Open'],
        ]],
        'equipment' => ['label' => 'Equipment matching', 'rows' => [
            ['#WASH-1052', 'Mobile Car Wash at Home', 'Provider water + customer power', 'unassigned', 'Needs tank', 'Assign'],
        ]],
        'reminders' => ['label' => 'Premium reminders', 'rows' => [
            ['R-311', 'Mobile Car Wash at Home', 'Sent Oct 14 · Delivered', 'reminder_due', 'Detail Team', 'Open'],
            ['R-298', 'Garbage Bin Cleaning', 'Confirmed → BIN-1043', 'confirmed', 'Team B', 'Open'],
            ['R-276', 'Lawn Mowing', 'Skipped this cycle', 'skipped', 'Team D', 'Open'],
        ]],
        'issues' => ['label' => 'Customer issues', 'rows' => [
            ['CASE-88', 'Fall Cleanup #FALL-1031', 'Bags left on boulevard', 'disputed', 'Support', 'Review'],
        ]],
    ],
    'teams' => [['Team A', 'busy'], ['Team B', 'available'], ['Team C', 'busy']],
    'weather' => ['title' => 'Snow expected · Edmonton', 'text' => 'Exterior car-wash and lawn appointments may need rescheduling.'],
    'payments' => [['authorized', 6], ['paid', 17], ['failed', 1], ['partially_refunded', 1]],
];
