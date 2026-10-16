<?php
// fixtures/visits.php: SAMPLE DATA ONLY. The signed-in customer's current visit per service, as shown on
// each service page (doc2 p2-7). Sir Paul: GET /api/bookings?client_id=&service=&active=1 plus
// GET /api/bookings/{id}/events for the activity strip. One status value drives every panel (doc2 p8 build rules).
// status: requested | quote_pending | confirmed | assigned | change_requested | on_the_way | arrived | in_progress | paused | completed | disputed
return [
    'garbage-bin-cleaning' => [
        'id' => 'BIN-1043', 'date' => 'Wednesday, May 14', 'window' => '1:00 PM – 3:00 PM', 'team' => 'Team B', 'team_note' => 'Assigned to your visit',
        'status' => 'on_the_way', 'eta' => '12:50 PM', 'eta_label' => 'Estimated arrival', 'updated' => '12:40 PM', 'plan' => 'premium',
        'steps' => [['Assigned', '12:10 PM'], ['On the way', '12:40 PM'], ['Arrived', ''], ['Cleaning', ''], ['Completed', '']], 'at' => 1,
        'activity' => [['12:05 PM', 'Booking confirmed', 'Your Garbage Bin Cleaning visit has been scheduled.'], ['12:10 PM', 'Team assigned', 'Team B has been assigned to your visit.'], ['12:40 PM', 'Trip started', 'Your team is on the way.']],
    ],
    'mobile-mechanics' => [
        'id' => 'MECH-1042', 'date' => 'Tuesday', 'window' => '10 AM – 12 PM', 'team' => 'Team A', 'team_note' => 'Assigned mobile mechanic team', 'team_label' => 'Assigned Mechanic',
        'status' => 'on_the_way', 'eta' => '10:20 AM', 'eta_label' => 'ETA', 'updated' => '10:05 AM', 'plan' => 'premium', 'badge' => 'Active booking',
        'change_hint' => 'Move to an available Wednesday window.', 'msg' => 'Message mechanic',
        'steps' => [['Assigned', '10:00 AM'], ['On the way', '10:05 AM'], ['Arrived', ''], ['Repair in progress', ''], ['Completed', '']], 'at' => 1,
        'activity' => [['Today · 9:15 AM', 'Quote approved', 'Work scope reviewed and approved.'], ['Today · 9:18 AM', 'Appointment confirmed', 'Tuesday · 10 AM – 12 PM.'], ['Today · 9:25 AM', 'Mechanic assigned', 'Team A assigned to your visit.']],
    ],
    'snow-removal' => [
        'id' => 'SNOW-1045', 'date' => 'Today', 'window' => '9:00 AM – 11:00 AM', 'team' => 'Team A', 'team_note' => '2 crew members', 'areas' => 'Driveway, Walkway',
        'status' => 'on_the_way', 'eta' => '9:40 AM', 'eta_label' => 'ETA', 'updated' => '8:12 AM', 'plan' => 'premium', 'badge' => 'Active',
        'steps' => [['Assigned', '8:05 AM'], ['On route', '8:12 AM'], ['Arrived', ''], ['Clearing', ''], ['Completed', '']], 'at' => 1, 'vertical' => true,
        'activity' => [['7:20 AM', 'Snow event confirmed', 'Dispatch confirmed a snow event for your area.', 'snow'], ['8:05 AM', 'Route assigned', 'Team A assigned to your visit (#SNOW-1045).', 'file-text'], ['8:12 AM', 'Team on route', 'Team A is on route. ETA 9:40 AM.', 'truck']],
        'snowfall' => ['updated' => '7:20 AM', 'title' => 'Snow event confirmed by dispatch', 'text' => 'Crews are operating in your area. Conditions may change during the day.'],
        'later' => ['current' => 'Today, 9:00 AM – 11:00 AM', 'proposed' => 'Today, 2:00 PM – 4:00 PM'],
    ],
    'fall-cleanup' => [
        'id' => 'FALL-1046', 'date' => 'Thursday', 'window' => '1–3 PM', 'team' => 'Cleanup Team C', 'status' => 'on_the_way', 'eta' => '1:20 PM', 'eta_label' => 'ETA', 'updated' => '1:05 PM',
        'plan' => 'premium', 'badge' => 'Active booking', 'photo' => 'summer-cleanup',
        'steps' => [['Assigned', ''], ['On the way', ''], ['Arrived', ''], ['Cleanup', ''], ['Completed', '']], 'at' => 1,
        'activity' => [['Oct 22 · 10:22 AM', 'Scope approved', '', 'file-text'], ['Oct 22 · 10:25 AM', 'Booking confirmed', '', 'calendar-check'], ['Oct 23 · 9:10 AM', 'Team assigned', '', 'people']],
        'activity_style' => 'list',
    ],
    'lawn-mowing' => [
        'id' => 'LAWN-1047', 'date' => 'Friday, 25 Apr', 'window' => '9 – 11 AM', 'team' => 'Lawn Team D', 'team_note' => '2 team members', 'status' => 'on_the_way', 'eta' => '9:30 AM', 'eta_label' => 'ETA', 'updated' => '9:10 AM',
        'plan' => 'premium', 'badge' => 'On the way',
        'steps' => [['Assigned', '8:45 AM'], ['On the way', '9:10 AM'], ['Arrived', ''], ['Mowing', ''], ['Completed', '']], 'at' => 1,
        'activity' => [['Fri, 25 Apr · 8:32 AM', 'Booking confirmed', 'Your booking #LAWN-1047 has been confirmed.'], ['Fri, 25 Apr · 8:45 AM', 'Team assigned', 'Lawn Team D has been assigned to your visit.'], ['Fri, 25 Apr · 9:10 AM', 'Trip started', 'The team is on the way. ETA 9:30 AM.', 'truck']],
    ],
    'car-wash' => [
        'id' => 'WASH-1048', 'date' => 'Saturday', 'window' => '10:00 AM – 12:00 PM', 'team' => 'Team E', 'package' => 'Full Detail', 'status' => 'on_the_way', 'eta' => '10:20 AM', 'eta_label' => 'ETA', 'updated' => '10:05 AM',
        'plan' => 'premium', 'badge' => 'On the way', 'layout' => 'table',
        'steps' => [['Assigned', '9:45 AM'], ['On the way', '10:05 AM'], ['Arrived', ''], ['Washing', ''], ['Completed', '']], 'at' => 1,
        'activity' => [['Saturday, 9:12 AM', 'Booking confirmed', 'Your booking has been received.'], ['Saturday, 9:28 AM', 'Equipment matched', 'Team E assigned. Water and power will be provided.'], ['Saturday, 10:05 AM', 'Trip started', 'Our team is on the way to your home.']],
        'activity_style' => 'vertical',
    ],
];
