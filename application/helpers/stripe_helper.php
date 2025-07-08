<?php
if (!function_exists('AddStripe')) {
    function AddStripe()
    {
        require_once(APPPATH . "libraries/stripe_lib/vendor/autoload.php");
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY_LIVE'] ?? '');
    }
}

if (!function_exists('AddStripetest')) {
    function AddStripetest()
    {
        require_once(APPPATH . "libraries/stripe_lib/vendor/autoload.php");
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY_TEST'] ?? '');
    }
}

if (!function_exists('StripeKey')) {
    function StripeKey()
    {
        return $_ENV['STRIPE_SECRET_KEY_TEST_1'] ?? '';
    }
}
