<?php
// fixtures/notifications.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/notifications (could extend notifications_log).
// type: due | snow | queue | info. 'queue' items render the "let others go first / pick a time window" options.
return [
    ['type' => 'due', 'icon' => 'trash', 'title' => 'Bin wash due today', 'text' => 'Nuahn Team B arrives 11:00 AM – 1:00 PM. Leave bins at the curb.', 'time' => '7:00 AM'],
    ['type' => 'snow', 'icon' => 'cloud-snow', 'title' => 'Snow expected tomorrow', 'text' => '5–10 cm overnight in Edmonton. Your booking remains confirmed; dispatch will confirm any change here.', 'time' => '6:30 AM'],
    ['type' => 'queue', 'icon' => 'list-ol', 'title' => 'You are #3 in Nuahn Team A’s queue', 'text' => 'Estimated clearing 6:40 – 7:10 AM tomorrow.', 'time' => '6:30 AM', 'position' => 3, 'windows' => ['6:00 – 8:00 AM', '8:00 – 10:00 AM', '10:00 AM – 12:00 PM']],
    ['type' => 'info', 'icon' => 'calendar-check', 'title' => 'Mechanic quote pending', 'text' => 'Quote requested. No repair slot is promised until you approve a quote.', 'time' => 'Tue'],
];
