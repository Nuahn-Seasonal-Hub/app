<?php
// fixtures/services.php — SAMPLE DATA ONLY (UI preview). Sir Paul: replace with GET /api/services.
// Each service drives the dynamic "Book a service" form (public/book.php).
// fields[]: name, label, type (number|select|text|chips), plus min/max/default/options.
return [
    'garbage-can-cleaning' => [
        'name' => 'Garbage Can Cleaning', 'short' => 'Bin washing', 'icon' => 'trash',
        'photo' => 'garbage-can-cleaning', 'alt' => 'Two green wheelie bins beside a white fence',
        'tagline' => 'Sanitize and deodorize your bins.',
        'blurb' => 'Hot-water wash, sanitize and deodorize, right at your curb.',
        'price' => 30, 'unit' => 'per visit (up to 2 bins)', 'per_extra' => 10, 'extra_field' => 'bins', 'included' => 2,
        'fields' => [
            ['name' => 'bins', 'label' => 'Number of bins', 'type' => 'number', 'min' => 1, 'max' => 6, 'default' => 2],
            ['name' => 'bin_types', 'label' => 'Bin types', 'type' => 'chips', 'options' => ['Garbage', 'Recycling', 'Compost'], 'default' => ['Garbage', 'Recycling']],
        ],
        'premium_freq' => ['Every 2 weeks', 'Monthly'],
        'season' => 'All year',
    ],
    'mobile-mechanics' => [
        'name' => 'Mobile Mechanics', 'short' => 'Mechanic', 'icon' => 'wrench-adjustable',
        'photo' => 'mobile-mechanic', 'alt' => 'Mechanic working under the hood of a car',
        'tagline' => 'Diagnostics and repairs in your driveway.',
        'blurb' => 'Diagnostics, oil changes, brakes and battery swaps at home.',
        'price' => 89, 'unit' => 'diagnostic visit',
        'fields' => [
            ['name' => 'vehicle', 'label' => 'Vehicle (year, make, model)', 'type' => 'text', 'placeholder' => 'e.g. 2019 Toyota RAV4'],
            ['name' => 'issue', 'label' => 'What do you need?', 'type' => 'select', 'options' => ['Diagnostics / check engine', 'Oil change', 'Brakes', 'Battery', 'Winter tire swap', 'Other']],
        ],
        'premium_freq' => ['Monthly check & diagnostics', 'Every 3 months'],
        'season' => 'All year',
    ],
    'mobile-detailing' => [
        'name' => 'Mobile Detailing', 'short' => 'Detailing', 'icon' => 'car-front',
        'photo' => 'mobile-detailing', 'alt' => 'Car covered in foam during a wash',
        'tagline' => 'Showroom shine, delivered.',
        'blurb' => 'Interior and exterior detailing wherever your car is parked.',
        'price' => 120, 'unit' => 'per vehicle',
        'fields' => [
            ['name' => 'vehicle_size', 'label' => 'Vehicle size', 'type' => 'select', 'options' => ['Car / sedan', 'SUV / crossover', 'Truck / van']],
            ['name' => 'package', 'label' => 'Package', 'type' => 'chips', 'options' => ['Exterior', 'Interior', 'Salt removal'], 'default' => ['Exterior']],
        ],
        'premium_freq' => ['Monthly', 'Every 2 months'],
        'season' => 'All year',
    ],
    'snow-removal' => [
        'name' => 'Snow Removal', 'short' => 'Snow', 'icon' => 'snow',
        'photo' => 'snow-removal', 'alt' => 'Orange snow plow clearing a snowy road',
        'tagline' => 'Cleared before you leave for work.',
        'blurb' => 'Driveways and walkways cleared, with live queue position on snow days.',
        'price' => 45, 'unit' => 'per clearing',
        'fields' => [
            ['name' => 'driveway', 'label' => 'Driveway size', 'type' => 'select', 'options' => ['Single (1 car)', 'Double (2 cars)', 'Triple / long']],
            ['name' => 'extras', 'label' => 'Also clear', 'type' => 'chips', 'options' => ['Walkway', 'Steps', 'Salt / ice melt'], 'default' => ['Walkway']],
        ],
        'premium_freq' => ['Every snowfall (seasonal)', 'Snowfalls over 5 cm'],
        'season' => 'Winter',
    ],
    'lawn-mowing' => [
        'name' => 'Lawn Mowing', 'short' => 'Lawn', 'icon' => 'scissors',
        'photo' => 'lawn-mowing', 'alt' => 'Red lawn mower on green grass',
        'tagline' => 'Crisp lines, every week.',
        'blurb' => 'Mow, trim and edge, with clippings bagged or mulched.',
        'price' => 40, 'unit' => 'per cut',
        'fields' => [
            ['name' => 'yard', 'label' => 'Yard size', 'type' => 'select', 'options' => ['Small (front only)', 'Medium (front + back)', 'Large']],
            ['name' => 'clippings', 'label' => 'Clippings', 'type' => 'chips', 'options' => ['Bag', 'Mulch'], 'default' => ['Mulch']],
        ],
        'premium_freq' => ['Weekly', 'Every 2 weeks'],
        'season' => 'Spring – Fall',
    ],
    'summer-cleanup' => [
        'name' => 'Summer Clean-up', 'short' => 'Clean-up', 'icon' => 'tree',
        'photo' => 'summer-cleanup', 'alt' => 'Raking leaves and garden debris',
        'tagline' => 'Yard, garage and gutters, sorted.',
        'blurb' => 'Yard debris, garden beds, gutters and garage clean-outs.',
        'price' => 75, 'unit' => 'per hour (2 hr min)', 'multiply_field' => 'hours',
        'fields' => [
            ['name' => 'areas', 'label' => 'Areas', 'type' => 'chips', 'options' => ['Yard', 'Garden beds', 'Gutters', 'Garage'], 'default' => ['Yard']],
            ['name' => 'hours', 'label' => 'Estimated hours', 'type' => 'number', 'min' => 2, 'max' => 8, 'default' => 2],
        ],
        'premium_freq' => ['Spring + Fall', 'Monthly in summer'],
        'season' => 'Spring – Fall',
    ],
];
