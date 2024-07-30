<?php

namespace App\Services;

use GuzzleHttp\Client;

class BinanceService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.binance.com',
        ]);
    }

    public function getPrice($symbol)
    {
        $response = $this->client->get("/api/v3/ticker/price", [
            'query' => ['symbol' => $symbol]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getHistoricalData($symbol, $interval = '4h', $limit = 100)
    {
        $response = $this->client->get("/api/v3/klines", [
            'query' => [
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

}