<?php

namespace App\Filament\Widgets;

use App\Models\BkashTransaction;
use Filament\Widgets\ChartWidget;

class ChannelVolumeChart extends ChartWidget
{
    protected static ?string $heading = 'Channel Settlement Distribution (Volume BDT)';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $a2aAmount = (float) BkashTransaction::where('transaction_type', 'A2A')
            ->whereIn('status_id', [BkashTransaction::STATUS_FINAL_AUTHORIZED, BkashTransaction::STATUS_CBS_SUCCESS])
            ->sum('amount');

        $beftnAmount = (float) BkashTransaction::where('transaction_type', 'BEFTN')
            ->whereIn('status_id', [BkashTransaction::STATUS_FINAL_AUTHORIZED, BkashTransaction::STATUS_CBS_SUCCESS])
            ->sum('amount');

        $rtgsAmount = (float) BkashTransaction::where('transaction_type', 'RTGS')
            ->whereIn('status_id', [BkashTransaction::STATUS_FINAL_AUTHORIZED, BkashTransaction::STATUS_CBS_SUCCESS])
            ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Settled Volume (BDT)',
                    'data' => [
                        round($a2aAmount, 2),
                        round($beftnAmount, 2),
                        round($rtgsAmount, 2),
                    ],
                    'backgroundColor' => [
                        '#10b981', // Emerald for A2A
                        '#f59e0b', // Amber for BEFTN
                        '#ef4444', // Rose for RTGS
                    ],
                    'borderColor' => 'transparent',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => [
                'Account to Account (A2A)',
                'BEFTN Clearing',
                'RTGS Settlement',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
