<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now        = Carbon::now();
        $startMonth = $now->copy()->startOfMonth();
        $startLast  = $now->copy()->subMonth()->startOfMonth();
        $endLast    = $now->copy()->subMonth()->endOfMonth();

        // ── Rechargements wallet ce mois ─────────────────────────
        $rechargesMois = WalletTransaction::where('type', 'recharge')
            ->where('payment_status', 'success')
            ->whereBetween('created_at', [$startMonth, $now])
            ->sum('amount');

        $rechargesDernier = WalletTransaction::where('type', 'recharge')
            ->where('payment_status', 'success')
            ->whereBetween('created_at', [$startLast, $endLast])
            ->sum('amount');

        $rechargeDiff = $rechargesDernier > 0
            ? round((($rechargesMois - $rechargesDernier) / $rechargesDernier) * 100, 1)
            : ($rechargesMois > 0 ? 100 : 0);

        // ── Revenus réservations (bus) ce mois ───────────────────
        $revenusReservations = Booking::whereIn('payment_status', ['completed', 'confirmed'])
            ->whereBetween('created_at', [$startMonth, $now])
            ->sum('total_price');

        $revenusReservationsDernier = Booking::whereIn('payment_status', ['completed', 'confirmed'])
            ->whereBetween('created_at', [$startLast, $endLast])
            ->sum('total_price');

        // ── Abonnements chauffeurs ce mois ───────────────────────
        $abonnements = DB::table('driver_subscriptions')
            ->where('month', $now->format('Y-m'))
            ->sum('amount');

        $abonnementsDernier = DB::table('driver_subscriptions')
            ->where('month', $now->copy()->subMonth()->format('Y-m'))
            ->sum('amount');

        // ── Nouveaux utilisateurs ce mois ────────────────────────
        $nouveauxUsers = User::whereBetween('created_at', [$startMonth, $now])->count();
        $nouveauxUsersDernier = User::whereBetween('created_at', [$startLast, $endLast])->count();

        // ── Total utilisateurs ───────────────────────────────────
        $totalUsers   = User::count();
        $totalDrivers = User::where('is_driver', true)->count();

        // ── Réservations ce mois ─────────────────────────────────
        $reservationsMois = Booking::whereBetween('created_at', [$startMonth, $now])->count();
        $reservationsDernier = Booking::whereBetween('created_at', [$startLast, $endLast])->count();

        return [
            Stat::make('Rechargements wallet (' . $now->locale('fr')->isoFormat('MMMM') . ')', number_format($rechargesMois, 0, ',', ' ') . ' FCFA')
                ->description($rechargeDiff >= 0
                    ? '+' . $rechargeDiff . '% vs mois dernier'
                    : $rechargeDiff . '% vs mois dernier'
                )
                ->descriptionIcon($rechargeDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($rechargeDiff >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Revenus réservations bus (' . $now->locale('fr')->isoFormat('MMMM') . ')', number_format($revenusReservations, 0, ',', ' ') . ' FCFA')
                ->description($revenusReservationsDernier > 0
                    ? round((($revenusReservations - $revenusReservationsDernier) / $revenusReservationsDernier) * 100, 1) . '% vs mois dernier'
                    : 'Pas de données mois dernier'
                )
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info')
                ->icon('heroicon-o-ticket'),

            Stat::make('Abonnements chauffeurs (' . $now->locale('fr')->isoFormat('MMMM') . ')', number_format($abonnements, 0, ',', ' ') . ' FCFA')
                ->description(round($abonnements / 1000) . ' chauffeurs abonnés · ' . ($abonnementsDernier > 0 ? round($abonnementsDernier / 1000) . ' mois dernier' : '0 mois dernier'))
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('warning')
                ->icon('heroicon-o-star'),

            Stat::make('Réservations ce mois', $reservationsMois)
                ->description($reservationsDernier > 0
                    ? ($reservationsMois >= $reservationsDernier ? '+' : '') . ($reservationsMois - $reservationsDernier) . ' vs mois dernier'
                    : 'Nouveau mois'
                )
                ->descriptionIcon($reservationsMois >= $reservationsDernier ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($reservationsMois >= $reservationsDernier ? 'success' : 'danger')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('Utilisateurs inscrits', $totalUsers)
                ->description($nouveauxUsers . ' nouveaux ce mois · ' . $totalDrivers . ' chauffeurs')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary')
                ->icon('heroicon-o-users'),
        ];
    }
}
