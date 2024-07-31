<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BinanceService;
use App\Services\TradingService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessTradingSignals extends Command
{
    protected $signature = 'trading:process-signals';
    protected $description = 'Process trading signals for multiple symbols and send to Telegram';

    protected $binanceService;
    protected $tradingService;
    protected $telegramService;

    public function __construct(BinanceService $binanceService, TradingService $tradingService, TelegramService $telegramService)
    {
        parent::__construct();
        $this->binanceService = $binanceService;
        $this->tradingService = $tradingService;
        $this->telegramService = $telegramService;
    }

    public function handle()
    {
        $symbols = explode(',', env('CRYPTO_SYMBOLS', 'BTCUSDT'));
        $interval = env('TIME_FRAME', '1m');

        foreach ($symbols as $symbol) {
            $data = $this->binanceService->getCandlestickData($symbol, $interval);
            $signals = $this->tradingService->generateTradeSignals($data);

            foreach ($signals as $signal) {
                $cacheKey = "signal_{$symbol}";

                $lastSignal = Cache::get($cacheKey);
                
                if ($lastSignal && $lastSignal['price'] == $signal['price'] && $lastSignal['type'] == $signal['type']) {
                    $this->telegramService->sendMessage("-- TESTED --");
                    continue;
                }

                Cache::put($cacheKey, $signal, now()->addHour());

                $message = ucfirst($signal['type']) . " signal for {$symbol} generated at price " . $signal['price'];
                $this->telegramService->sendMessage($message);
            }
        }
    }
}