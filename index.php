<?php
// index.php - Entry point for Nuahn Seasonal Hub.
// Serves the same landing page as public/index.php (no second navbar, no redirect hop).
chdir(__DIR__ . '/public');
require __DIR__ . '/public/index.php';
