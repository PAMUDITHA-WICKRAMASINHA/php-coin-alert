<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CryptoController;
use App\Services\BinanceService;
use App\Services\IndicatorService;

class GetCryptoAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:get-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get crypto trading alerts';

    /**
     * The Binance service instance.
     *
     * @var \App\Services\BinanceService
     */
    protected $binanceService;

    /**
     * The Indicator service instance.
     *
     * @var \App\Services\IndicatorService
     */
    protected $indicatorService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BinanceService $binanceService, IndicatorService $indicatorService)
    {
        parent::__construct();
        
        $this->binanceService = $binanceService;
        $this->indicatorService = $indicatorService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cryptoController = new CryptoController($this->binanceService, $this->indicatorService);
        $cryptoController->getAlerts();
        return Command::SUCCESS;
    }
}