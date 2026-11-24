<?php
// Copy this file to config/onboarding.local.php to enable live SMS OTP or Google Places.
// Leave unconfigured (or omit) to run in Beta Sandbox mode (OTP codes display inline for testing).

return [
    // Optional Twilio SMS settings for live phone OTP delivery
    'twilio_sid'   => '',
    'twilio_token' => '',
    'twilio_from'  => '', // e.g. '+15875551234'

    // Optional Google Maps Places API key (for Canada Google Business autocomplete in Step 3)
    'google_places_key' => '',

    // Optional sender address for email verification messages
    'mail_from' => 'no-reply@nuahnseasonalhub.com',
];
