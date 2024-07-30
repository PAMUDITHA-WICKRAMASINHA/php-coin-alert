<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BinanceService;
use App\Services\IndicatorService;
use Illuminate\Support\Facades\Cache;

class CryptoController extends Controller
{
    protected $binanceService;
    protected $indicatorService;

    public function __construct(BinanceService $binanceService, IndicatorService $indicatorService)
    {
        $this->binanceService = $binanceService;
        $this->indicatorService = $indicatorService;
    }

    public function getPrice()
    {
        $symbol = env('CRYPTO_SYMBOL', 'BTCUSDT');
        $price = $this->binanceService->getPrice($symbol);
        return response()->json($price);
    }

    public function getAlerts()
    {
        $symbols = explode(',', env('CRYPTO_SYMBOLS', 'BTCUSDT'));
        $time = env('TIME_RANGE', '4h');

        $results = [];

        foreach ($symbols as $symbol) {
            $data = $this->binanceService->getHistoricalData($symbol, $time);
            $sma = $this->indicatorService->calculateSMA($data, 20);
            $ema = $this->indicatorService->calculateEMA($data, 20);
            $rsi = $this->indicatorService->calculateRSI($data);
            $bollingerBands = $this->indicatorService->calculateBollingerBands($data);

            $alerts = $this->indicatorService->generateAlerts($data, $sma, $ema, $rsi, $bollingerBands);

            $decision = $this->analyzeAlerts($alerts, $symbol);

            if($decision != 'hold'){
                $this->sendTelegramMessage($symbol . "\nDecision: " . $decision);
            }

            $results[] = [
                'symbol' => $symbol,
                'decision' => $decision,
            ];
        }

        return response()->json($results);
    }
    
    private function analyzeAlerts(array $alerts, $symbol)
    {
        $buyCount = 0;
        $sellCount = 0;
        $decision = 'hold';
    
        foreach ($alerts as $alert) {
            if ($alert['type'] === 'buy') {
                $buyCount++;
            } elseif ($alert['type'] === 'sell') {
                $sellCount++;
            }
        }
    
        if ($buyCount > $sellCount) {
            $decision = 'buy';
        } elseif ($sellCount > $buyCount) {
            $decision = 'sell';
        } else {
            $decision = 'hold';
        }
    
        $cacheKey = 'last_alert_' . $symbol;
    
        if (Cache::has($cacheKey)) {
            $lastDecision = Cache::get($cacheKey);
    
            if ($lastDecision !== $decision) {
                Cache::put($cacheKey, $decision);
                if ($decision !== 'hold') {
                    return $decision;
                }
                return 'hold';
            }
    
            return 'hold';
        } else {
            Cache::put($cacheKey, $decision);
            return $decision;
        }
    }
    
    
    private function sendTelegramMessage($message)
    {
        $telegramToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
        $postFields = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}