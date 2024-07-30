<?php

namespace App\Services;

class IndicatorService
{
    public function calculateSMA(array $data, $period)
    {
        $sma = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($i < $period - 1) {
                $sma[] = null;
            } else {
                $sum = 0;
                for ($j = 0; $j < $period; $j++) {
                    $sum += $data[$i - $j][4];
                }
                $sma[] = $sum / $period;
            }
        }
        return $sma;
    }

    public function calculateEMA(array $data, $period)
    {
        $ema = [];
        $multiplier = 2 / ($period + 1);

        // Initialize the first EMA with the SMA
        $sma = array_sum(array_slice(array_column($data, 4), 0, $period)) / $period;
        $ema[] = $sma;

        for ($i = $period; $i < count($data); $i++) {
            $ema[] = ($data[$i][4] - end($ema)) * $multiplier + end($ema);
        }

        return $ema;
    }

    public function calculateRSI(array $data, $period = 14)
    {
        $rsi = [];
        $gains = 0;
        $losses = 0;

        for ($i = 1; $i <= $period; $i++) {
            $change = $data[$i][4] - $data[$i - 1][4];
            if ($change > 0) {
                $gains += $change;
            } else {
                $losses -= $change;
            }
        }

        $averageGain = $gains / $period;
        $averageLoss = $losses / $period;

        for ($i = $period; $i < count($data); $i++) {
            $change = $data[$i][4] - $data[$i - 1][4];
            if ($change > 0) {
                $gain = $change;
                $loss = 0;
            } else {
                $gain = 0;
                $loss = -$change;
            }

            $averageGain = (($averageGain * ($period - 1)) + $gain) / $period;
            $averageLoss = (($averageLoss * ($period - 1)) + $loss) / $period;

            $rs = $averageGain / $averageLoss;
            $rsi[] = 100 - (100 / (1 + $rs));
        }

        return $rsi;
    }

    public function calculateBollingerBands(array $data, $period = 20, $multiplier = 2)
    {
        $sma = $this->calculateSMA($data, $period);
        $bands = ['upper' => [], 'lower' => []];

        for ($i = 0; $i < count($data); $i++) {
            if ($i < $period - 1) {
                $bands['upper'][] = null;
                $bands['lower'][] = null;
            } else {
                $sum = 0;
                for ($j = 0; $j < $period; $j++) {
                    $sum += pow($data[$i - $j][4] - $sma[$i], 2);
                }
                $stdDev = sqrt($sum / $period);
                $bands['upper'][] = $sma[$i] + ($multiplier * $stdDev);
                $bands['lower'][] = $sma[$i] - ($multiplier * $stdDev);
            }
        }

        return $bands;
    }

    public function generateAlerts(array $data, array $sma, array $ema, array $rsi, array $bollingerBands)
    {
        $alerts = [];
        $minCount = min(count($data), count($sma), count($ema), count($rsi) + 1, count($bollingerBands['upper']), count($bollingerBands['lower']));
    
        for ($i = 1; $i < $minCount; $i++) {
            if (isset($sma[$i], $ema[$i], $rsi[$i - 1], $bollingerBands['upper'][$i], $bollingerBands['lower'][$i])) {
                if ($sma[$i] !== null && $ema[$i] !== null) {
                    if ($data[$i][4] > $sma[$i] && $data[$i - 1][4] <= $sma[$i - 1]) {
                        $alerts[] = ['type' => 'buy', 'price' => $data[$i][4], 'indicator' => 'SMA'];
                    } elseif ($data[$i][4] < $sma[$i] && $data[$i - 1][4] >= $sma[$i - 1]) {
                        $alerts[] = ['type' => 'sell', 'price' => $data[$i][4], 'indicator' => 'SMA'];
                    }
    
                    if ($data[$i][4] > $ema[$i] && $data[$i - 1][4] <= $ema[$i - 1]) {
                        $alerts[] = ['type' => 'buy', 'price' => $data[$i][4], 'indicator' => 'EMA'];
                    } elseif ($data[$i][4] < $ema[$i] && $data[$i - 1][4] >= $ema[$i - 1]) {
                        $alerts[] = ['type' => 'sell', 'price' => $data[$i][4], 'indicator' => 'EMA'];
                    }
    
                    if ($rsi[$i - 1] < 30 && $rsi[$i] >= 30) {
                        $alerts[] = ['type' => 'buy', 'price' => $data[$i][4], 'indicator' => 'RSI'];
                    } elseif ($rsi[$i - 1] > 70 && $rsi[$i] <= 70) {
                        $alerts[] = ['type' => 'sell', 'price' => $data[$i][4], 'indicator' => 'RSI'];
                    }
    
                    if ($data[$i][4] < $bollingerBands['lower'][$i]) {
                        $alerts[] = ['type' => 'buy', 'price' => $data[$i][4], 'indicator' => 'Bollinger Bands'];
                    } elseif ($data[$i][4] > $bollingerBands['upper'][$i]) {
                        $alerts[] = ['type' => 'sell', 'price' => $data[$i][4], 'indicator' => 'Bollinger Bands'];
                    }
                }
            }
        }
    
        return $alerts;
    }
    
}