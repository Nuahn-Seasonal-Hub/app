<?php
// fixtures/proof_of_work.php — SAMPLE DATA ONLY. Sir Paul: replace with provider-uploaded photos (POST /api/bookings/{id}/photos)
// Completion evidence only (doc2 p14-15). Payment is a separate state (doc2 p21): see 'payment' and nu_payment_chip().
return [
    'booking' => 'NSH-1042',
    'before' => ['photo' => 'work-before', 'alt' => 'Snow-covered driveway before clearing', 'time' => '9:24 AM'],
    'after' => ['photo' => 'work-after', 'alt' => 'Driveway after clearing', 'time' => '9:51 AM'],
    'payment' => ['amount' => 45, 'tax' => 2.25, 'currency' => 'CAD', 'state' => 'authorized'],
];
