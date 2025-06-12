<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\ChartWidget;

class ClicksChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Clicks (Past 30 Days)';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Use a seeded random for more consistent data but still natural variations
        $seed = date('Y-m-d'); // Changes daily for subtle variation
        srand(crc32($seed . 'clicks'));
        
        $baseValue = 450;
        $data = [];
        $labels = [];
        
        // Generate 30 days of data
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');
            
            // Create natural growth pattern with some ups and downs
            $dayProgress = (29 - $i) / 29; // 0 to 1 progression
            $growth = 4 + ($dayProgress * 15); // Gradual increase in growth rate
            $variance = (rand(0, 20) - 10) * 0.8; // Some natural variation
            
            $baseValue += $growth + $variance;
            
            // Ensure no significant drops
            if ($baseValue < 450) {
                $baseValue = 450 + rand(0, 5);
            }
            
            $data[] = round($baseValue);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Clicks',
                    'data' => $data,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#f59e0b',
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