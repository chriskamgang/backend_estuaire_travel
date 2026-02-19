<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Reports extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Finances';
    protected static ?int    $navigationSort   = 2;
    protected static string  $view             = 'filament.pages.reports';

    public static function getNavigationLabel(): string
    {
        return 'Rapports financiers';
    }

    public string $period = 'month'; // month | quarter | year | all

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Exporter rapport CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action('exportCsv'),
        ];
    }

    public function exportCsv()
    {
        $data = $this->getReportData();

        $csv  = "Rapport financier Estuaire Travel\n";
        $csv .= "Généré le," . now()->format('d/m/Y H:i') . "\n\n";

        $csv .= "=== REVENUS WALLET ===\n";
        $csv .= "Mois,Rechargements (FCFA),Débits (FCFA),Abonnements chauffeurs (FCFA)\n";
        foreach ($data['monthly'] as $row) {
            $csv .= implode(',', [
                '"' . $row['label'] . '"',
                number_format($row['recharges'], 2, '.', ''),
                number_format($row['debits'], 2, '.', ''),
                number_format($row['subscriptions'], 2, '.', ''),
            ]) . "\n";
        }

        $csv .= "\n=== RÉSERVATIONS BUS ===\n";
        $csv .= "Mois,Nb réservations,Revenus (FCFA)\n";
        foreach ($data['monthly'] as $row) {
            $csv .= implode(',', [
                '"' . $row['label'] . '"',
                $row['bookings_count'],
                number_format($row['bookings_revenue'], 2, '.', ''),
            ]) . "\n";
        }

        $csv .= "\n=== TOTAUX ===\n";
        $csv .= "Total rechargements," . number_format($data['totals']['recharges'], 2, '.', '') . "\n";
        $csv .= "Total débits," . number_format($data['totals']['debits'], 2, '.', '') . "\n";
        $csv .= "Total abonnements," . number_format($data['totals']['subscriptions'], 2, '.', '') . "\n";
        $csv .= "Total réservations bus," . number_format($data['totals']['bookings_revenue'], 2, '.', '') . "\n";
        $csv .= "Nb réservations," . $data['totals']['bookings_count'] . "\n";
        $csv .= "Nb chauffeurs abonnés ce mois," . $data['totals']['drivers_subscribed'] . "\n";
        $csv .= "Total utilisateurs," . $data['totals']['total_users'] . "\n";

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF" . $csv; // BOM pour Excel
        }, 'rapport_' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function getReportData(): array
    {
        $months = 12;
        $monthly = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month    = Carbon::now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $start    = $month->copy()->startOfMonth();
            $end      = $month->copy()->endOfMonth();

            $recharges = WalletTransaction::where('type', 'recharge')
                ->where('payment_status', 'success')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');

            $debits = WalletTransaction::where('type', 'debit')
                ->where('payment_status', 'success')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');

            $subscriptions = DB::table('driver_subscriptions')
                ->where('month', $monthKey)
                ->sum('amount');

            $bookingsCount = Booking::whereIn('payment_status', ['completed', 'confirmed'])
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $bookingsRevenue = Booking::whereIn('payment_status', ['completed', 'confirmed'])
                ->whereBetween('created_at', [$start, $end])
                ->sum('total_price');

            $monthly[] = [
                'label'           => $month->locale('fr')->isoFormat('MMM YYYY'),
                'month_key'       => $monthKey,
                'recharges'       => (float) $recharges,
                'debits'          => (float) $debits,
                'subscriptions'   => (float) $subscriptions,
                'bookings_count'  => $bookingsCount,
                'bookings_revenue'=> (float) $bookingsRevenue,
            ];
        }

        $now = Carbon::now();
        $totals = [
            'recharges'          => (float) WalletTransaction::where('type', 'recharge')->where('payment_status', 'success')->sum('amount'),
            'debits'             => (float) WalletTransaction::where('type', 'debit')->where('payment_status', 'success')->sum('amount'),
            'subscriptions'      => (float) DB::table('driver_subscriptions')->sum('amount'),
            'bookings_revenue'   => (float) Booking::whereIn('payment_status', ['completed', 'confirmed'])->sum('total_price'),
            'bookings_count'     => Booking::whereIn('payment_status', ['completed', 'confirmed'])->count(),
            'drivers_subscribed' => DB::table('driver_subscriptions')->where('month', $now->format('Y-m'))->count(),
            'total_users'        => User::count(),
            'total_drivers'      => User::where('is_driver', true)->count(),
        ];

        // Top 5 utilisateurs par rechargements
        $topRechargers = WalletTransaction::where('type', 'recharge')
            ->where('payment_status', 'success')
            ->select('user_id', DB::raw('SUM(amount) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('user:id,name,phone')
            ->get();

        return compact('monthly', 'totals', 'topRechargers');
    }

    protected function getViewData(): array
    {
        return $this->getReportData();
    }
}
