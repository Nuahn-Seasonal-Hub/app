<?php
// fixtures/proof_of_work.php — SAMPLE DATA ONLY. Sir Paul: replace with provider-uploaded photos (POST /api/bookings/{id}/photos)
// and an escrow state machine: held -> awaiting_confirmation -> released | disputed (payment provider, e.g. Stripe Connect).
return [
    'booking' => 'BK-1042',
    'before' => ['photo' => 'work-before', 'alt' => 'Snow-covered driveway before clearing', 'time' => '9:24 AM'],
    'after' => ['photo' => 'work-after', 'alt' => 'Driveway after clearing', 'time' => '9:51 AM'],
    'escrow' => ['amount' => 45, 'tax' => 2.25, 'currency' => 'CAD', 'state' => 'awaiting_confirmation'],
];
