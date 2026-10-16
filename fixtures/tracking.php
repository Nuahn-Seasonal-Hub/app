<?php
// fixtures/tracking.php: SAMPLE DATA ONLY (doc1 p3, doc2 p14). Sir Paul: replace with GET /api/bookings/{id}/tracking
// (provider GPS pings with permission during the accepted trip + status events), ideally pushed via SSE/WebSocket.
// route[] = [lat,lng] points the vehicle marker animates along. location_updated drives the stale-location label.
return [
    'booking' => 'NSH-1042',
    'service' => 'snow-removal',
    'status' => 'on_the_way',
    'address' => '123 Maple Grove Drive, Edmonton, AB',
    'window' => 'Today 8:00 – 11:00 AM',
    'eta' => '9:20 AM', 'eta_min' => 12, 'eta_range' => '9:15 – 9:35 AM',
    'location_updated' => '9:05 AM', 'stale_since' => '9:06 AM',
    'team' => ['name' => 'Nuahn Team A', 'members' => 'Jordan & Priya', 'vehicle' => 'Plow truck · CXT 4821', 'rating' => 4.9, 'note' => 'Your service team for this booking.'],
    'home' => [53.4891, -113.5262],
    'route' => [[53.4972, -113.5112], [53.4962, -113.5131], [53.4951, -113.5150], [53.4940, -113.5168], [53.4929, -113.5189], [53.4918, -113.5208], [53.4908, -113.5228], [53.4899, -113.5246], [53.4891, -113.5262]],
    'timeline' => [
        ['key' => 'confirmed', 'label' => 'Confirmed', 'time' => '7:40 AM', 'done' => true],
        ['key' => 'assigned', 'label' => 'Assigned', 'time' => '8:05 AM', 'done' => true],
        ['key' => 'on_the_way', 'label' => 'On the way', 'time' => '9:05 AM', 'done' => true, 'current' => true],
        ['key' => 'arrived', 'label' => 'Arrived', 'time' => '', 'done' => false],
        ['key' => 'in_progress', 'label' => 'In progress', 'time' => '', 'done' => false],
        ['key' => 'completed', 'label' => 'Completed', 'time' => '', 'done' => false],
    ],
    'messages' => [
        ['from' => 'team', 'name' => 'Nuahn Team A', 'time' => '9:06 AM', 'text' => 'We are on our way.'],
        ['from' => 'me', 'name' => 'You', 'time' => '9:07 AM', 'text' => 'Please remember the front walkway.'],
        ['from' => 'team', 'name' => 'Nuahn Team A', 'time' => '9:08 AM', 'text' => 'Yes, it is included in your booking.'],
    ],
    'weather' => ['temp' => '−5°C', 'text' => 'Snow expected', 'note' => 'Your booking remains confirmed.'],
    'checklist' => ['Driveway cleared to pavement', 'Front walkway and steps cleared', 'Ice melt applied to steps', 'Snow piled away from garage door'],
    'invoice' => ['number' => 'INV-1042', 'amount' => 45.00, 'tax' => 2.25, 'currency' => 'CAD', 'payment' => 'authorized'],
];
