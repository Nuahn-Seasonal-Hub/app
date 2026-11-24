<?php
// public/register_provider.php — route all provider signups to the 3-step onboarding wizard.
require_once("../config/init.php");
header('Location: ' . nu_url('public/provider_onboarding.php'), true, 302);
exit;
