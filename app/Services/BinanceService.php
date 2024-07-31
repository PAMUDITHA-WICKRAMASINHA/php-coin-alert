<?php

namespace App\Services;

use GuzzleHttp\Client;

class BinanceService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getCandlestickData($symbol, $interval = "1m", $limit = 100)
    {
        $url = "https://api.binance.com/api/v3/klines";
        $response = $this->client->get($url, [
            'query' => [
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ]
        ]);

        $candlesticks = json_decode($response->getBody()->getContents(), true);

        $formattedData = [];
        foreach ($candlesticks as $candle) {
            $formattedData[] = [
                'open' => $candle[1],
                'close' => $candle[4],
                'high' => $candle[2],
                'low' => $candle[3],
                'volume' => $candle[5],
                'time' => $candle[0],
            ];
        }

        return $formattedData;
    }
}