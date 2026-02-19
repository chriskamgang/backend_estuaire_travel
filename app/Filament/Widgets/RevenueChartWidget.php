<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\WalletTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenus mensuels (6 derniers mois)';
    protected static ?int    $sort    = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels         = [];
        $recharges      = [];
        $reservations   = [];
        $abonnements    = [];

        for ($i = 5; $i >= 0; $i--) {
            $month     = Carbon::now()->subMonths($i);
            $monthKey  = $month->format('Y-m');
            $labels[]  = $month->locale('fr')->isoFormat('MMM YYYY');

            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            // Rechargements wallet
            $recharges[] = (float) WalletTransaction::where('type', 'recharge')
                ->where('payment_status', 'success')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');

            // Revenus réservations bus
            $reservations[] = (float) Booking::whereIn('payment_status', ['completed', 'confirmed'])
                ->whereBetween('created_at', [$start, $end])
                ->sum('total_price');

            // Abonnements chauffeurs
            $abonnements[] = (float) DB::table('driver_subscriptions')
                ->where('month', $monthKey)
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Rechargements wallet',
                    'data'            => $recharges,
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Réservations bus',
                    'data'            => $reservations,
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Abonnements chauffeurs',
                    'data'            => $abonnements,
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
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
            'plugins' => [
                'legend' => ['display' => true],
                'tooltip' => [
                    'callbacks' => [
                        // Formatted in the chart as-is — FCFA suffix shown in label
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return value.toLocaleString('fr-FR') + ' FCFA'; }",
                    ],
                ],
            ],
        ];
    }
}
