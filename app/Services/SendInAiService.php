<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SendInAiService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = "https://api.sendinai.com/sender";
        $this->token = config('services.sendinai.token');
    }

    public function sendTemplate($phone, $templateName, $params = [])
    {
        $payload = array_merge([
            "token" => $this->token,
            "phone" => $phone,
            "template_name" => $templateName,
            "template_language" => "EN_US",
        ], $params);

        return Http::post($this->baseUrl, $payload);
    }
}
