<?php
// fixtures/visits.php: SAMPLE DATA ONLY (live services only: snow removal and mobile mechanics; locked services have no visits). The signed-in customer's current visit per service, as shown on
// each service page (doc2 p2-7). Sir Paul: GET /api/bookings?client_id=&service=&active=1 plus
// GET /api/bookings/{id}/events for the activity strip. One status value drives every panel (doc2 p8 build rules).
// status: requested | quote_pending | confirmed | assigned | change_requested | on_the_way | arrived | in_progress | paused | completed | disputed
return [
    'mobile-mechanics' => [
        'id' => 'MECH-1042', 'date' => 'Tuesday', 'window' => '10 AM – 12 PM', 'team' => 'Marcus Tran', 'team_note' => 'Independent mobile mechanic', 'team_label' => 'Assigned Mechanic',
        'status' => 'on_the_way', 'eta' => '10:20 AM', 'eta_label' => 'ETA', 'updated' => '10:05 AM', 'plan' => 'premium', 'badge' => 'Active booking',
        'change_hint' => 'Move to an available Wednesday window.', 'msg' => 'Message mechanic',
        'steps' => [['Confirmed', '10:00 AM'], ['On the way', '10:05 AM'], ['Arrived', ''], ['Repair in progress', ''], ['Completed', '']], 'at' => 1,
        'activity' => [['Today · 9:15 AM', 'Quote approved', 'Work scope reviewed and approved.'], ['Today · 9:18 AM', 'Appointment confirmed', 'Tuesday · 10 AM – 12 PM.'], ['Today · 9:25 AM', 'Mechanic assigned', 'Jordan Mercer assigned to your visit.']],
    ],
    'snow-removal' => [
        'id' => 'SNOW-1045', 'date' => 'Today', 'window' => '9:00 AM – 11:00 AM', 'team' => 'Jordan Mercer', 'team_note' => 'Independent contractor', 'areas' => 'Driveway, Walkway',
        'status' => 'on_the_way', 'eta' => '9:40 AM', 'eta_label' => 'ETA', 'updated' => '8:12 AM', 'plan' => 'premium', 'badge' => 'Active',
        'steps' => [['Confirmed', '8:05 AM'], ['On route', '8:12 AM'], ['Arrived', ''], ['Clearing', ''], ['Completed', '']], 'at' => 1, 'vertical' => true,
        'activity' => [['7:20 AM', 'Snow event confirmed', 'Nuahn confirmed a snow event for your area.', 'snow'], ['8:05 AM', 'Route assigned', 'Jordan Mercer assigned to your visit (#SNOW-1045).', 'file-text'], ['8:12 AM', 'Contractor on route', 'Jordan is on route. ETA 9:40 AM.', 'truck']],
        'snowfall' => ['updated' => '7:20 AM', 'title' => 'Snow event confirmed by Nuahn', 'text' => 'Contractors are working in your area. Conditions may change during the day.'],
        'later' => ['current' => 'Today, 9:00 AM – 11:00 AM', 'proposed' => 'Today, 2:00 PM – 4:00 PM'],
    ],
];
