<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// Copy to config/payments.local.php (untracked, gitignored) and fill in. NEVER commit real keys.
// The same values can come from environment variables instead (they win over this file):
//   STRIPE_SECRET_KEY, STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET, NUAHN_PAYMENTS (sandbox|stripe)
// With no keys, the app runs on the built-in sandbox provider and shows a "Test payments" label.
return [
    'stripe_secret'         => '',   // sk_test_... (use test keys until launch)
    'stripe_publishable'    => '',   // pk_test_...
    'stripe_webhook_secret' => '',   // whsec_... from the webhook endpoint https://<host>/public/stripe_webhook.php
    'mode'                  => '',   // '' = automatic (stripe when both keys are set), 'sandbox' = force the sandbox
];
