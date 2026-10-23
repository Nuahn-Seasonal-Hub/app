<?php
// config/preview.example.php: copy to config/preview.local.php (untracked) and set a long random key,
// or set the NUAHN_PREVIEW_KEY environment variable. Generate one with:  openssl rand -hex 24
// Used by includes/gate.php for the ?preview=<key> bypass on the public domain.
return ['key' => 'CHANGE-ME-long-random-string'];
