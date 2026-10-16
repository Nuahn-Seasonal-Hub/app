<?php
// fixtures/events.php: SAMPLE DATA ONLY. Booking activity, evidence and notifications (doc2 p15, p21-22).
// Sir Paul: GET /api/bookings/{id}/events (actor, timestamp, type, before/after value), GET /api/notifications
// (channel + delivery state; one event id per template so retries never double-send), POST /api/bookings/{id}/rating,
// POST /api/cases {booking_id, reason, photos}. Events and messages are separate streams.
return [
    'booking' => [
        'id' => 'WASH-1048', 'service' => 'car-wash', 'title' => 'Car wash #1048', 'team' => 'Detail Team E', 'status' => 'completed', 'payment' => 'paid',
        'date' => 'Saturday, Oct 10', 'duration' => '42 min on site', 'utilities' => 'Provider water + provider power', 'package' => 'Full Detail · Sedan',
        'timeline' => [
            ['08:05', 'Booking confirmed', 'approved water / power arrangement saved.', 'Customer', 'calendar-check'],
            ['08:15', 'Detail Team assigned', '', 'Dispatch', 'people'],
            ['09:02', 'Team on the way', 'ETA 09:25, location updated.', 'Team E', 'truck'],
            ['09:26', 'Arrived', 'before photos uploaded.', 'Team E', 'geo-alt-fill'],
            ['09:30', 'Work started', '', 'Team E', 'play-circle'],
            ['10:12', 'Work completed', 'after photos and checklist submitted.', 'Team E', 'check-circle-fill'],
            ['10:15', 'Invoice available', 'payment state shown separately.', 'System', 'receipt'],
        ],
        'checklist' => [['Pre-rinse and foam', true], ['Hand wash and dry', true], ['Interior vacuum and wipe', true], ['Windows and tires', true]],
        'notes' => 'Light salt residue on rocker panels removed. Customer’s tap not used; tank water only.',
        'invoice' => [['Full Detail · Sedan', 110.00], ['Provider water + power', 10.00]], 'tax' => 6.00,
    ],
    'notifications' => [
        ['booking_confirmed', 'Booking confirmed', 'WASH-1048 · Saturday 10:00 AM – 12:00 PM', 'Oct 9 · 8:05 PM', 'email + in-app', 'delivered'],
        ['team_assigned', 'Team assigned', 'Detail Team E will arrive with water and power.', 'Oct 10 · 8:15 AM', 'push', 'delivered'],
        ['reminder_due', 'Reminder due', 'Monthly car-care reminder from Nuahn Detail Team.', 'Oct 14 · 9:00 AM', 'email', 'read'],
        ['reschedule_requested', 'Reschedule requested', 'SNOW-1045 · later today 2:00 – 4:00 PM (pending dispatch).', 'Today · 7:42 AM', 'in-app', 'delivered'],
        ['reschedule_confirmed', 'Reschedule confirmed', 'MECH-1042 moved to Wednesday 1:00 – 3:00 PM.', 'Today · 7:50 AM', 'SMS + in-app', 'delivered'],
        ['reschedule_declined', 'Reschedule declined', 'Requested window is full. Your original visit stays booked.', 'Yesterday · 4:10 PM', 'in-app', 'delivered'],
        ['trip_started', 'Trip started', 'Team A is on the way. ETA 9:20 AM.', 'Today · 9:05 AM', 'push', 'delivered'],
        ['eta_delay', 'ETA delay', 'Team A is running ~15 min late (previous job overran).', 'Today · 9:12 AM', 'push', 'failed'],
        ['weather_hold', 'Weather hold', 'Rain may affect LAWN-1047. Your team is reviewing the schedule.', 'Today · 6:30 AM', 'in-app', 'delivered'],
        ['work_started', 'Work started', 'Snow clearing started at your home.', 'Today · 9:24 AM', 'in-app', 'pending'],
        ['quote_approval', 'Quote ready for approval', 'Q-2207 v1 · Brake Pad Replacement · expires Fri 5:00 PM.', 'Yesterday · 2:40 PM', 'email + in-app', 'delivered'],
        ['completion', 'Work completed', 'WASH-1048 · photos and checklist are ready.', 'Oct 10 · 10:12 AM', 'push', 'delivered'],
        ['payment_outcome', 'Payment received', 'CAD 126.00 paid for WASH-1048.', 'Oct 10 · 10:16 AM', 'email', 'delivered'],
    ],
];
