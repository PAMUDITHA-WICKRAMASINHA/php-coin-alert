<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TradingService
{
    public function calculateRSI($data, $period = 14)
    {
        $gain = $loss = [];
        for ($i = 1; $i < count($data); $i++) {
            $change = $data[$i]['close'] - $data[$i - 1]['close'];
            if ($change > 0) {
                $gain[] = $change;
                $loss[] = 0;
            } else {
                $gain[] = 0;
                $loss[] = abs($change);
            }
        }

        $averageGain = array_sum(array_slice($gain, 0, $period)) / $period;
        $averageLoss = array_sum(array_slice($loss, 0, $period)) / $period;

        $rsiValues = [];
        for ($i = $period; $i < count($data); $i++) {
            $averageGain = (($averageGain * ($period - 1)) + $gain[$i - 1]) / $period;
            $averageLoss = (($averageLoss * ($period - 1)) + $loss[$i - 1]) / $period;
            $rs = $averageGain / $averageLoss;
            $rsiValues[] = 100 - (100 / (1 + $rs));
        }

        return array_merge(array_fill(0, $period, null), $rsiValues);
    }

    public function getEngulfingCandles($data)
    {
        $engulfingCandles = [];
        for ($i = 1; $i < count($data); $i++) {
            $bullishCandle = $data[$i]['close'] >= $data[$i - 1]['open'] && $data[$i - 1]['close'] < $data[$i - 1]['open'];
            $bearishCandle = $data[$i]['close'] <= $data[$i - 1]['open'] && $data[$i - 1]['close'] > $data[$i - 1]['open'];

            $engulfingCandles[] = [
                'bullish' => $bullishCandle,
                'bearish' => $bearishCandle,
            ];
        }

        return $engulfingCandles;
    }

    public function generateTradeSignals($data, $rsiPeriod = 14, $rsiOverBought = 70, $rsiOverSold = 30)
    {
        $rsiValues = $this->calculateRSI($data, $rsiPeriod);
        $engulfingCandles = $this->getEngulfingCandles($data);

        $signals = [];
        for ($i = $rsiPeriod; $i < count($data); $i++) {
            $isRSIOS = $rsiValues[$i] <= $rsiOverSold;
            $isRSIOB = $rsiValues[$i] >= $rsiOverBought;

            $bullishCandle = $engulfingCandles[$i - 1]['bullish'];
            $bearishCandle = $engulfingCandles[$i - 1]['bearish'];

            if (($isRSIOS || ($i > 0 && $rsiValues[$i - 1] <= $rsiOverSold) || ($i > 1 && $rsiValues[$i - 2] <= $rsiOverSold)) && $bullishCandle) {
                $signals[] = ['type' => 'buy', 'price' => $data[$i]['close']];
            } elseif (($isRSIOB || ($i > 0 && $rsiValues[$i - 1] >= $rsiOverBought) || ($i > 1 && $rsiValues[$i - 2] >= $rsiOverBought)) && $bearishCandle) {
                $signals[] = ['type' => 'sell', 'price' => $data[$i]['close']];
            }
        }

        return $signals;
    }
}