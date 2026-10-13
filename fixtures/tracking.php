<?php
// fixtures/tracking.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/bookings/{id}/tracking (provider GPS pings + status events),
// ideally pushed via SSE/WebSocket. route[] = [lat,lng] points the vehicle marker animates along.
return [
    'booking' => 'BK-1042',
    'service' => 'snow-removal',
    'status' => 'on_the_way',
    'eta' => '9:20 AM', 'eta_min' => 12,
    'team' => ['name' => 'Nuahn Team A', 'members' => 'Jordan & Priya', 'vehicle' => 'Plow truck · CXT 4821', 'rating' => 4.9],
    'home' => [51.0035, -114.1295],
    'route' => [[51.0168, -114.1068], [51.0150, -114.1105], [51.0131, -114.1139], [51.0112, -114.1170], [51.0093, -114.1199], [51.0079, -114.1228], [51.0062, -114.1251], [51.0049, -114.1272], [51.0035, -114.1295]],
    'timeline' => [
        ['key' => 'confirmed', 'label' => 'Confirmed', 'time' => '8:12 AM', 'done' => true],
        ['key' => 'assigned', 'label' => 'Assigned', 'time' => '8:35 AM', 'done' => true, 'note' => 'Nuahn Team A'],
        ['key' => 'on_the_way', 'label' => 'On the way', 'time' => '9:05 AM', 'done' => true, 'current' => true],
        ['key' => 'in_progress', 'label' => 'In progress', 'time' => '', 'done' => false],
        ['key' => 'completed', 'label' => 'Completed', 'time' => '', 'done' => false],
    ],
];
