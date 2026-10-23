<?php
// fixtures/bookings.php: SAMPLE DATA ONLY. Sir Paul: replace with GET /api/bookings?client_id= (needs a bookings table).
// status uses the shared vocabulary in includes/ui.php nu_booking_status(); payment is a separate state (doc2 p21).
return [
    ['id' => 'NSH-1042', 'service' => 'snow-removal', 'when' => 'Today', 'date' => '+0 day', 'window' => '8:00 – 11:00 AM', 'status' => 'on_the_way', 'team' => 'Nuahn Team A', 'plan' => 'Premium', 'freq' => 'Monthly check-in', 'address' => '123 Maple Grove Drive, Edmonton, AB', 'price' => 45, 'payment' => 'authorized'],
    ['id' => 'SNOW-1045', 'service' => 'snow-removal', 'when' => 'Tomorrow', 'date' => '+1 day', 'window' => '9:00 – 11:00 AM', 'status' => 'change_requested', 'team' => 'Team A', 'plan' => 'Premium', 'freq' => 'Storm plan', 'address' => '123 Maple Grove Drive, Edmonton, AB', 'price' => 45, 'queue' => 3, 'payment' => 'unpaid'],
    ['id' => 'MECH-1042', 'service' => 'mobile-mechanics', 'when' => 'Tue', 'date' => '+4 day', 'window' => '10 AM – 12 PM', 'status' => 'confirmed', 'team' => 'Mechanic Team A', 'plan' => 'Basic', 'freq' => 'One visit', 'address' => '123 Maple Grove Drive, Edmonton, AB', 'price' => 412.65, 'payment' => 'authorized'],
    ['id' => 'MECH-1051', 'service' => 'mobile-mechanics', 'when' => 'Requested', 'date' => '+5 day', 'window' => 'No slot yet', 'status' => 'quote_pending', 'team' => 'Awaiting quote', 'plan' => 'Basic', 'freq' => 'One visit', 'address' => '123 Maple Grove Drive, Edmonton, AB', 'price' => 0, 'payment' => 'unpaid'],
    ['id' => 'SNOW-1031', 'service' => 'snow-removal', 'when' => 'Last Sat', 'date' => '-6 day', 'window' => '10:00 AM – 12:00 PM', 'status' => 'completed', 'team' => 'Team A', 'plan' => 'Premium', 'freq' => 'Storm plan', 'address' => '123 Maple Grove Drive, Edmonton, AB', 'price' => 120, 'payment' => 'paid'],
    ['id' => 'MECH-1031', 'service' => 'mobile-mechanics', 'when' => 'Oct 2', 'date' => '-14 day', 'window' => '1 – 3 PM', 'status' => 'disputed', 'team' => 'Mechanic Team B', 'plan' => 'Basic', 'freq' => 'One visit', 'address' => '123 Maple Grove Drive, Edmonton, AB', 'price' => 120, 'payment' => 'partially_refunded'],
];
