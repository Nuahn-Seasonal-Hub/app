<?php
// fixtures/events.php: SAMPLE DATA ONLY. Booking activity, evidence and notifications (doc2 p15, p21-22).
// Sir Paul: GET /api/bookings/{id}/events (actor, timestamp, type, before/after value), GET /api/notifications
// (channel + delivery state; one event id per template so retries never double-send), POST /api/bookings/{id}/rating,
// POST /api/cases {booking_id, reason, photos}. Events and messages are separate streams.
return [
    'booking' => [
        'id' => 'SNOW-1031', 'service' => 'snow-removal', 'title' => 'Snow clearing #1031', 'team' => 'Jordan Mercer', 'contractor' => 'c-1001', 'status' => 'approved', 'payment' => 'paid',
        'date' => 'Saturday, Oct 17', 'duration' => '38 min on site', 'utilities' => 'Contractor brings equipment and ice melt', 'package' => 'Standard driveway · front walkway · steps',
        'timeline' => [
            ['08:05', 'Task posted', 'Target budget CAD 45. Funds held when you accepted an offer.', 'Customer', 'calendar-check'],
            ['08:15', 'Offer accepted', 'Jordan Mercer · CAD 48 · funds held securely.', 'Customer', 'people'],
            ['09:02', 'Contractor on the way', 'ETA 09:25, location updated.', 'Jordan Mercer', 'truck'],
            ['09:26', 'Arrived', 'before photos uploaded.', 'Jordan Mercer', 'geo-alt-fill'],
            ['09:30', 'Work started', '', 'Jordan Mercer', 'play-circle'],
            ['10:08', 'Completion report submitted', 'before and after photos and checklist.', 'Jordan Mercer', 'check-circle-fill'],
            ['10:15', 'You approved the work', 'Payment released to the contractor.', 'Customer', 'check2-all'],
        ],
        'checklist' => [['Driveway cleared to pavement', true], ['Front walkway and steps cleared', true], ['Ice melt applied to steps', true], ['Snow piled away from the garage door', true]],
        'notes' => 'Heavy wet snow. Edges finished by hand and the steps were salted twice.',
        'invoice' => [['Snow clearing · standard driveway + walkway', 40.00], ['Ice melt add-on', 8.00]], 'tax' => 2.40,
    ],
    'notifications' => [
        ['offer_received', 'New offer on your task', 'TASK-2051 · Jordan Mercer offered CAD 48, about 40 min away.', 'Today · 8:21 AM', 'push + in-app', 'delivered'],
        ['funds_held', 'Funds held securely', 'CAD 48.00 held for SNOW-1031 until you approve the work.', 'Oct 17 · 8:15 AM', 'email + in-app', 'delivered'],
        ['contractor_accepted', 'Contractor accepted', 'Jordan Mercer will arrive with ice melt on board.', 'Oct 17 · 8:15 AM', 'push', 'delivered'],
        ['trip_started', 'Trip started', 'Jordan is on the way. ETA 9:20 AM.', 'Today · 9:05 AM', 'push', 'delivered'],
        ['eta_delay', 'ETA delay', 'Jordan is running ~15 min late (previous job overran).', 'Today · 9:12 AM', 'push', 'failed'],
        ['work_started', 'Work started', 'Snow clearing started at your home.', 'Today · 9:24 AM', 'in-app', 'pending'],
        ['approval_needed', 'Review and approve', 'SNOW-1031 · before and after photos are ready. Payment releases when you approve.', 'Oct 17 · 10:08 AM', 'push + in-app', 'delivered'],
        ['reschedule_requested', 'Later visit requested', 'SNOW-1045 · later today 2:00 – 4:00 PM (waiting for your contractor).', 'Today · 7:42 AM', 'in-app', 'delivered'],
        ['reschedule_confirmed', 'Reschedule confirmed', 'MECH-1042 moved to Wednesday 1:00 – 3:00 PM.', 'Today · 7:50 AM', 'SMS + in-app', 'delivered'],
        ['reschedule_declined', 'Reschedule declined', 'Requested window is not available. Your original visit stays booked.', 'Yesterday · 4:10 PM', 'in-app', 'delivered'],
        ['quote_approval', 'Quote ready for approval', 'Q-2207 v1 · Brake Pad Replacement · expires Fri 5:00 PM.', 'Yesterday · 2:40 PM', 'email + in-app', 'delivered'],
        ['payout_outcome', 'Payment released', 'CAD 48.00 released to Jordan Mercer for SNOW-1031.', 'Oct 17 · 10:16 AM', 'email', 'delivered'],
        ['dispute_opened', 'Problem reported', 'CASE-88 · funds stay held while Nuahn reviews your report.', 'Today · 8:02 AM', 'email + in-app', 'delivered'],
    ],
];
