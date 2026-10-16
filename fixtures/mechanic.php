<?php
// fixtures/mechanic.php: SAMPLE DATA ONLY. Mobile mechanic service menu (doc2 p16-18, doc1 p14-17).
// Sir Paul: GET /api/services/mobile-mechanics/categories?lat=&lng= (availability per confirmed location),
// POST /api/mechanic-requests (returns request id + state: searching | quote_pending | no_provider),
// GET /api/quotes/{id} (versioned), POST /api/quotes/{id}/approve|decline|question.
// fields[] reuse the service form types from fixtures/services.php.
$vehicle = ['type' => 'vehicle4', 'name' => 'vehicle', 'label' => 'Vehicle details'];
return [
    'location' => ['label' => 'Service location', 'value' => 'Edmonton, AB'],
    'roadside' => [
        'flat-tire'      => ['name' => 'Flat Tire', 'icon' => 'disc', 'tone' => 'orange', 'fields' => [$vehicle, ['type' => 'radios', 'name' => 'wheel', 'label' => 'Which wheel?', 'options' => ['Front left', 'Front right', 'Rear left', 'Rear right']], ['type' => 'radios', 'name' => 'spare', 'label' => 'Is a usable spare available?', 'options' => ['Yes', 'No', 'Not sure']]]],
        'dead-battery'   => ['name' => 'Dead Battery', 'icon' => 'battery-half', 'tone' => 'red', 'fields' => [$vehicle, ['type' => 'textarea', 'name' => 'access', 'label' => 'Access to the vehicle', 'placeholder' => 'e.g. underground parking level P2, keys with me']]],
        'lockout'        => ['name' => 'Lockout', 'icon' => 'lock-fill', 'tone' => 'blue', 'fields' => [$vehicle, ['type' => 'checks', 'name' => 'verify', 'label' => 'Verification', 'options' => ['I can show photo ID matching the registration', 'Keys are visible inside the vehicle']]]],
        'fuel-delivery'  => ['name' => 'Fuel Delivery', 'icon' => 'fuel-pump-fill', 'tone' => 'yellow', 'fields' => [$vehicle, ['type' => 'radios', 'name' => 'fuel', 'label' => 'Fuel type', 'options' => ['Regular', 'Premium', 'Diesel']], ['type' => 'radios', 'name' => 'qty', 'label' => 'Quantity', 'options' => ['10 L', '20 L']]]],
        'tire-pressure'  => ['name' => 'Tire Pressure', 'icon' => 'exclamation-circle', 'tone' => 'green', 'fields' => [$vehicle, ['type' => 'checks', 'name' => 'tires', 'label' => 'Affected tires / warning', 'options' => ['Warning light on', 'Visibly low tire', 'Slow leak']]]],
        'towing'         => ['name' => 'Towing', 'icon' => 'truck', 'tone' => 'purple', 'fields' => [$vehicle, ['type' => 'text', 'name' => 'destination', 'label' => 'Destination', 'placeholder' => 'Garage or address'], ['type' => 'radios', 'name' => 'condition', 'label' => 'Vehicle condition', 'options' => ['Rolls / steers', 'Does not roll', 'After a collision']]]],
    ],
    'maintenance' => [
        'oil-change'          => ['name' => 'Oil Change', 'icon' => 'droplet-half'],
        'battery-replacement' => ['name' => 'Battery Replacement', 'icon' => 'battery-charging'],
        'tire-rotation'       => ['name' => 'Tire Rotation', 'icon' => 'arrow-clockwise'],
        'brake-pads'          => ['name' => 'Brake Pad Replacement', 'icon' => 'disc', 'selected' => true],
        'fluid-top-off'       => ['name' => 'Fluid Top-Off', 'icon' => 'droplet-fill'],
        'check-engine'        => ['name' => 'Check Engine Light', 'icon' => 'exclamation-triangle'],
        'diagnostic'          => ['name' => 'Full Vehicle Diagnostic', 'icon' => 'activity'],
        'tire-replacement'    => ['name' => 'Tire Replacement', 'icon' => 'disc'],
    ],
    // Brake pad replacement form (doc2 p17). Other maintenance tiles reuse vehicle + symptoms + photo + location.
    'brake_symptoms' => ['Squeaking', 'Warning light', 'Grinding', 'Routine replacement'],
    'quote' => [
        'id' => 'Q-2207', 'version' => 1, 'booking' => 'MECH-1042', 'service' => 'Brake Pad Replacement', 'vehicle' => '2020 Toyota RAV4 · LE 2.5 L',
        'scope' => 'Replace front brake pads, inspect rotors and calipers, top up brake fluid, road test.',
        'items' => [
            ['Front brake pads (ceramic, set)', 'Parts', 1, 89.00],
            ['Brake hardware kit', 'Parts', 1, 18.50],
            ['Labour: front pads + inspection', 'Labour', 1.5, 95.00],
            ['Mobile call-out / diagnostic fee', 'Fee', 1, 49.00],
        ],
        'tax_rate' => 0.05, 'tax_label' => 'GST (5%)',
        'includes' => ['Rotor and caliper inspection', 'Brake fluid top-up', 'Road test'],
        'excludes' => ['Rotor resurfacing or replacement', 'Rear brakes'],
        'expires' => 'Valid until Fri, Oct 23, 5:00 PM', 'mechanic' => 'Mechanic Team A',
        'windows' => ['Tue · 10 AM – 12 PM', 'Wed · 9 – 11 AM', 'Wed · 1 – 3 PM', 'Thu · 3 – 5 PM'],
        'extra' => [
            'found' => 'Front rotors are below minimum thickness (scored).',
            'items' => [['Front rotors (pair)', 'Parts', 1, 142.00], ['Labour: rotor replacement', 'Labour', 0.8, 95.00]],
        ],
    ],
];
