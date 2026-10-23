<?php
// fixtures/notifications.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/notifications (could extend notifications_log).
// type: due | snow | queue | info. 'queue' items render the "let others go first / pick a time window" options.
return [
    ['type' => 'due', 'icon' => 'shield-lock-fill', 'title' => 'Approve the work to release payment', 'text' => 'Jordan Mercer finished SNOW-1031 and added before and after photos. Funds stay held until you approve.', 'time' => '10:08 AM', 'href' => 'approve.php?id=SNOW-1031'],
    ['type' => 'info', 'icon' => 'chat-square-text', 'title' => 'New offer on your task', 'text' => 'Jordan Mercer offered CAD 48 for TASK-2051, about 40 min away.', 'time' => '8:21 AM', 'href' => 'offers.php?state=many'],
    ['type' => 'snow', 'icon' => 'cloud-snow', 'title' => 'Snow expected tomorrow', 'text' => '5–10 cm overnight in Edmonton. Your booking remains confirmed; your contractor will confirm any change here.', 'time' => '6:30 AM'],
    ['type' => 'queue', 'icon' => 'list-ol', 'title' => 'You are #3 on Jordan’s list', 'text' => 'Estimated clearing 6:40 – 7:10 AM tomorrow.', 'time' => '6:30 AM', 'position' => 3, 'windows' => ['6:00 – 8:00 AM', '8:00 – 10:00 AM', '10:00 AM – 12:00 PM']],
    ['type' => 'info', 'icon' => 'calendar-check', 'title' => 'Mechanic quote pending', 'text' => 'Quote requested. No repair slot is promised until you approve a quote.', 'time' => 'Tue'],
];
