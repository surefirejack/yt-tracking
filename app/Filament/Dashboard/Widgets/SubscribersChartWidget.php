<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\ChartWidget;

class SubscribersChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Subscribers (Past 30 Days)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Use a seeded random for more consistent data but still natural variations
        $seed = date('Y-m-d'); // Changes daily for subtle variation
        srand(crc32($seed . 'subscribers'));
        
        $baseValue = 1250;
        $data = [];
        $labels = [];
        
        // Generate 30 days of data
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');
            
            // Create natural growth pattern with some ups and downs
            $dayProgress = (29 - $i) / 29; // 0 to 1 progression
            $growth = 8 + ($dayProgress * 20); // Gradual increase in growth rate
            $variance = (rand(0, 20) - 10) * 0.6; // Reduced variance for smoother curve
            
            $baseValue += $growth + $variance;
            
            // Ensure no significant drops
            if ($baseValue < 1250) {
                $baseValue = 1250 + rand(0, 10);
            }
            
            $data[] = round($baseValue);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Subscribers',
                    'data' => $data,
                    'borderColor' => '#14b8a6',
                    'backgroundColor' => 'rgba(20, 184, 166, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#14b8a6',
                    'pointRadius' => 3,
                ]
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.1)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
} 