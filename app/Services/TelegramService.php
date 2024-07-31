<?php

namespace App\Services;

use GuzzleHttp\Client;

class TelegramService
{
    protected $apiToken;
    protected $chatId;
    protected $client;

    public function __construct()
    {
        $this->apiToken = env('TELEGRAM_BOT_TOKEN', '');
        $this->chatId = env('TELEGRAM_CHAT_ID', '');
        $this->client = new Client();
    }

    public function sendMessage($message)
    {
        $url = "https://api.telegram.org/bot{$this->apiToken}/sendMessage";
        $response = $this->client->post($url, [
            'form_params' => [
                'chat_id' => $this->chatId,
                'text' => $message,
            ],
        ]);

        return $response;
    }
}