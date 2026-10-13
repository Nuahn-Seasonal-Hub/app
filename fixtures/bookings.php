<?php
// fixtures/bookings.php — SAMPLE DATA ONLY. Sir Paul: replace with GET /api/bookings?client_id= (needs a bookings table).
// status: requested | confirmed | assigned | on_the_way | in_progress | completed | cancelled
return [
    ['id' => 'BK-1042', 'service' => 'snow-removal', 'when' => 'Today', 'date' => '+0 day', 'window' => '8:00 – 11:00 AM', 'status' => 'on_the_way', 'team' => 'Nuahn Team A', 'plan' => 'Premium', 'freq' => 'Every snowfall', 'address' => '123 Maple Grove Dr SW, Calgary, AB', 'price' => 45],
    ['id' => 'BK-1043', 'service' => 'garbage-can-cleaning', 'when' => 'Today', 'date' => '+0 day', 'window' => '11:00 AM – 1:00 PM', 'status' => 'confirmed', 'team' => 'Nuahn Team B', 'plan' => 'Premium', 'freq' => 'Every 2 weeks', 'address' => '123 Maple Grove Dr SW, Calgary, AB', 'price' => 30],
    ['id' => 'BK-1044', 'service' => 'snow-removal', 'when' => 'Tomorrow', 'date' => '+1 day', 'window' => '6:00 – 8:00 AM', 'status' => 'confirmed', 'team' => 'Nuahn Team A', 'plan' => 'Premium', 'freq' => 'Every snowfall', 'address' => '123 Maple Grove Dr SW, Calgary, AB', 'price' => 45, 'queue' => 3],
    ['id' => 'BK-1051', 'service' => 'mobile-mechanics', 'when' => 'Mon', 'date' => '+4 day', 'window' => '1:00 – 3:00 PM', 'status' => 'requested', 'team' => 'Awaiting provider', 'plan' => 'Basic', 'freq' => 'One-time', 'address' => '123 Maple Grove Dr SW, Calgary, AB', 'price' => 89],
    ['id' => 'BK-1012', 'service' => 'mobile-detailing', 'when' => 'Last week', 'date' => '-6 day', 'window' => '10:00 AM', 'status' => 'completed', 'team' => 'Shine Crew', 'plan' => 'Basic', 'freq' => 'One-time', 'address' => '123 Maple Grove Dr SW, Calgary, AB', 'price' => 120],
];
