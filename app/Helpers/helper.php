<?php

use App\Models\Settings;
use Filament\Forms\Get;

function calculateTotal(Get $get): float
{
    $cost = (float) $get('cost');
    $discount = (float) $get('discount_percent');
    $gst = (float) $get('gst_percent');

    // discount
    $afterDiscount = $cost - ($cost * $discount / 100);

    // gst
    $withGst = $afterDiscount + ($afterDiscount * $gst / 100);

    return round($withGst, 2);
}

function send_privyr($name, $email = null, $phone = null, $display_name, $other_fields = [])
{
    $setting = Settings::first();
    $webhook_url = $setting->privyr_webhook_url;
    if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) return;
    // Data payload
    $data = [
        "name" => $name,
        "email" => $email,
        "phone" => $phone,
        "display_name" => $display_name,
        "other_fields" => $other_fields
    ];

    // Initialize cURL
    $ch = curl_init($webhook_url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        return false;
    } else {
        return true;
    }

    // Close cURL
    curl_close($ch);
}
