<?php
// fixtures/messages.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/threads and /api/threads/{id}/messages (needs threads + messages tables).
// 'me' => true marks messages sent by the signed-in user. 'actions' renders quick-reply chips (confirm / reschedule).
return [
    'threads' => [
        ['id' => 't1', 'name' => 'Jordan Mercer', 'about' => 'Snow Removal · Today', 'last' => 'On our way, about 12 minutes out.', 'time' => '9:13 AM', 'unread' => 2, 'initials' => 'JM'],
        ['id' => 't2', 'name' => 'Priya Nair', 'about' => 'Snow Removal · Offer on TASK-2051', 'last' => 'I can do steps and walkway by hand. Salt included.', 'time' => 'Yesterday', 'unread' => 1, 'initials' => 'PN'],
        ['id' => 't3', 'name' => 'Marcus Tran', 'about' => 'Mobile Mechanics · Mon', 'last' => 'Can you do 1–3 PM instead?', 'time' => 'Tue', 'unread' => 0, 'initials' => 'MT'],
        ['id' => 't4', 'name' => 'Jordan Mercer', 'about' => 'Snow Removal · SNOW-1031 completed', 'last' => 'Thanks! Before and after photos are uploaded.', 'time' => 'Oct 17', 'unread' => 0, 'initials' => 'JM'],
    ],
    'thread' => [
        'id' => 't3', 'name' => 'Marcus Tran', 'about' => 'Mobile Mechanics · MECH-1042', 'initials' => 'MT',
        'messages' => [
            ['me' => true, 'text' => 'Hi! Booked a diagnostic for Monday 10–12. Check engine light is on.', 'time' => 'Tue 4:02 PM'],
            ['me' => false, 'text' => 'Thanks Alice. I have another job that morning. Can you do 1–3 PM instead?', 'time' => 'Tue 4:20 PM'],
            ['me' => false, 'type' => 'proposal', 'title' => 'Reschedule request', 'text' => 'Mon · 1:00 – 3:00 PM', 'time' => 'Tue 4:20 PM'],
        ],
        'actions' => ['Confirm 1–3 PM', 'Suggest another time', 'Keep original'],
    ],
];
