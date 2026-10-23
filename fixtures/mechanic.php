<?php
// fixtures/mechanic.php: SAMPLE DATA ONLY. Mobile mechanics: Pathway A roadside (broadcast to nearby approved technicians, no scheduling)
// and Pathway B scheduled repair (scoped, quoted, approved). All prices are invented for the preview.
// Sir Paul: GET /api/services/mobile-mechanics/categories?lat=&lng= (availability per confirmed location),
// POST /api/mechanic-requests (returns request id + state: searching | quote_pending | no_provider),
// GET /api/quotes/{id} (versioned), POST /api/quotes/{id}/approve|decline|question.
// fields[] reuse the service form types from fixtures/services.php.
$vehicle = ['type' => 'vehicle4', 'name' => 'vehicle', 'label' => 'Vehicle details'];
return [
    'location' => ['label' => 'Service location', 'value' => 'Edmonton, AB'],
    'roadside' => [
        'flat-tire'      => ['name' => 'Flat tire', 'icon' => 'disc', 'tone' => 'orange', 'fields' => [$vehicle, ['type' => 'radios', 'name' => 'wheel', 'label' => 'Which wheel?', 'options' => ['Front left', 'Front right', 'Rear left', 'Rear right']], ['type' => 'radios', 'name' => 'spare', 'label' => 'Is a usable spare available?', 'options' => ['Yes', 'No', 'Not sure']]]],
        'dead-battery'   => ['name' => 'Battery boost', 'icon' => 'battery-half', 'tone' => 'red', 'fields' => [$vehicle, ['type' => 'textarea', 'name' => 'access', 'label' => 'Access to the vehicle', 'placeholder' => 'e.g. underground parking level P2, keys with me']]],
        'lockout'        => ['name' => 'Lockout', 'icon' => 'lock-fill', 'tone' => 'blue', 'fields' => [$vehicle, ['type' => 'checks', 'name' => 'verify', 'label' => 'Verification', 'options' => ['I can show photo ID matching the registration', 'Keys are visible inside the vehicle']]]],
        'fuel-delivery'  => ['name' => 'Emergency fuel', 'icon' => 'fuel-pump-fill', 'tone' => 'yellow', 'fields' => [$vehicle, ['type' => 'radios', 'name' => 'fuel', 'label' => 'Fuel type', 'options' => ['Regular', 'Premium', 'Diesel']], ['type' => 'radios', 'name' => 'qty', 'label' => 'Quantity', 'options' => ['10 L', '20 L']]]],
        'tire-pressure'  => ['name' => 'Tire pressure', 'icon' => 'exclamation-circle', 'tone' => 'green', 'fields' => [$vehicle, ['type' => 'checks', 'name' => 'tires', 'label' => 'Affected tires / warning', 'options' => ['Warning light on', 'Visibly low tire', 'Slow leak']]]],
        'towing'         => ['name' => 'Local towing', 'icon' => 'truck', 'tone' => 'purple', 'fields' => [$vehicle, ['type' => 'text', 'name' => 'destination', 'label' => 'Destination', 'placeholder' => 'Garage or address'], ['type' => 'radios', 'name' => 'condition', 'label' => 'Vehicle condition', 'options' => ['Rolls / steers', 'Does not roll', 'After a collision']]]],
    ],
    'roadside_est' => ['flat-tire' => '$85–$120', 'dead-battery' => '$70–$100', 'lockout' => '$80–$110', 'fuel-delivery' => '$65–$95', 'tire-pressure' => '$45–$65', 'towing' => '$110–$180'],
    'maintenance' => [
        'oil-change'          => ['name' => 'Oil and filter change', 'icon' => 'droplet-half', 'symptoms' => ['Due by mileage or date', 'Oil light on', 'Dark or gritty oil', 'Engine louder than usual']],
        'brake-pads'          => ['name' => 'Brake pad replacement', 'icon' => 'disc', 'selected' => true, 'symptoms' => ['Squeaking', 'Grinding', 'Warning light', 'Pedal feels soft', 'Routine replacement']],
        'battery-replacement' => ['name' => 'Battery replacement', 'icon' => 'battery-charging', 'symptoms' => ['Slow to start', 'Clicking when starting', 'Battery warning light', 'Battery over 4 years old']],
        'tire-rotation'       => ['name' => 'Tire rotation or swap', 'icon' => 'arrow-clockwise', 'symptoms' => ['Uneven tire wear', 'Seasonal swap (I have the tires)', 'Vibration at speed', 'Routine rotation']],
        'fluid-top-off'       => ['name' => 'Fluid top-off', 'icon' => 'droplet-fill', 'symptoms' => ['Low washer fluid', 'Low coolant', 'Low brake fluid', 'Low power-steering fluid']],
        'check-engine'        => ['name' => 'Check engine diagnostic', 'icon' => 'exclamation-triangle', 'symptoms' => ['Check engine light on', 'Rough idle', 'Loss of power', 'Poor fuel economy', 'Strange smell']],
    ],
    'vehicles' => ['Toyota' => ['RAV4', 'Corolla', 'Camry', 'Highlander'], 'Honda' => ['Civic', 'CR-V', 'Accord'], 'Ford' => ['F-150', 'Escape', 'Explorer'], 'Hyundai' => ['Elantra', 'Tucson', 'Santa Fe'], 'Chevrolet' => ['Silverado', 'Equinox', 'Malibu']],
    'trims' => ['Base', 'LE', 'XLE', 'Sport', 'Limited', 'Not sure'],
    'engines' => ['2.0 L 4-cyl', '2.5 L 4-cyl', '3.5 L V6', 'Hybrid', 'Diesel', 'Electric', 'Not sure'],
    'parking' => ['Flat driveway', 'Street parking', 'Parking lot', 'Underground garage', 'Gravel or soft ground', 'Highway shoulder'],
    'road_sit' => ['I am in a safe place', 'Hazard lights are on', 'I am on a highway or busy road', 'There is a child or pet in the vehicle'],
    'techs_nearby' => 6,
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
        'taxes' => [['GST (5%)', 0.05], ['PST (0%, not charged in Alberta)', 0.0]], 'tax_rate' => 0.05, 'tax_label' => 'GST (5%)',
        'includes' => ['Rotor and caliper inspection', 'Brake fluid top-up', 'Road test'],
        'excludes' => ['Rotor resurfacing or replacement', 'Rear brakes'],
        'expires' => 'Valid until Fri, Oct 23, 5:00 PM', 'mechanic' => 'Marcus Tran',
        'windows' => ['Tue · 10 AM – 12 PM', 'Wed · 9 – 11 AM', 'Wed · 1 – 3 PM', 'Thu · 3 – 5 PM'],
        'extra' => [
            'found' => 'Front rotors are below minimum thickness (scored).',
            'items' => [['Front rotors (pair)', 'Parts', 1, 142.00], ['Labour: rotor replacement', 'Labour', 0.8, 95.00]],
        ],
    ],
];
