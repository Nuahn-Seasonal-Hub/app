<?php
// fixtures/messages.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/threads and /api/threads/{id}/messages (needs threads + messages tables).
// 'me' => true marks messages sent by the signed-in user. 'actions' renders quick-reply chips (confirm / reschedule).
return [
    'threads' => [
        ['id' => 't1', 'name' => 'Nuahn Team A', 'about' => 'Snow Removal · Today', 'last' => 'On our way, about 12 minutes out.', 'time' => '9:13 AM', 'unread' => 2, 'initials' => 'TA'],
        ['id' => 't2', 'name' => 'Nuahn Team A', 'about' => 'Snow Removal · Tomorrow', 'last' => 'You’re #3 in the queue for tomorrow morning.', 'time' => 'Yesterday', 'unread' => 1, 'initials' => 'TA'],
        ['id' => 't3', 'name' => 'Mike’s Mobile Mechanics', 'about' => 'Mobile Mechanics · Mon', 'last' => 'Can you do 1–3 PM instead?', 'time' => 'Tue', 'unread' => 0, 'initials' => 'MM'],
        ['id' => 't4', 'name' => 'Shine Crew', 'about' => 'Mobile Detailing · Completed', 'last' => 'Thanks! Photos are uploaded.', 'time' => 'Oct 1', 'unread' => 0, 'initials' => 'SC'],
    ],
    'thread' => [
        'id' => 't3', 'name' => 'Mike’s Mobile Mechanics', 'about' => 'Mobile Mechanics · BK-1051', 'initials' => 'MM',
        'messages' => [
            ['me' => true, 'text' => 'Hi! Booked a diagnostic for Monday 10–12. Check engine light is on.', 'time' => 'Tue 4:02 PM'],
            ['me' => false, 'text' => 'Thanks Alice. I have a job across town that morning. Can you do 1–3 PM instead?', 'time' => 'Tue 4:20 PM'],
            ['me' => false, 'type' => 'proposal', 'title' => 'Reschedule request', 'text' => 'Mon · 1:00 – 3:00 PM', 'time' => 'Tue 4:20 PM'],
        ],
        'actions' => ['Confirm 1–3 PM', 'Suggest another time', 'Keep original'],
    ],
];
