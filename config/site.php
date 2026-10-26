<?php

// Replaces the Voyager `settings` table (site.* keys) removed with MOD-02.
// Override any value via the matching SITE_* env var.
return [
    'title' => env('SITE_TITLE', 'Jószomszédság Vigyázó Hálózata'),
    'description' => env('SITE_DESCRIPTION', 'Segíts a szomszédodnak!'),
    'logo' => env('SITE_LOGO', '/images/logo.png'),
    'organization_name' => env('SITE_ORG_NAME', 'Jószomszédság'),
    'organization_slogan' => env('SITE_ORG_SLOGAN', 'Segíts a szomszédodnak!'),
    'organization_address' => env('SITE_ORG_ADDRESS', ''),
    'organization_phone' => env('SITE_ORG_PHONE', ''),
    'organization_email' => env('SITE_ORG_EMAIL', ''),
];
