<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- TOTAUX GÉNÉRAUX                                        --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total rechargements</p>
            <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                {{ number_format($totals['recharges'], 0, ',', ' ') }} FCFA
            </p>
            <p class="mt-1 text-xs text-gray-400">Tous les temps</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Revenus réservations</p>
            <p class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($totals['bookings_revenue'], 0, ',', ' ') }} FCFA
            </p>
            <p class="mt-1 text-xs text-gray-400">{{ $totals['bookings_count'] }} réservations confirmées</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Abonnements chauffeurs</p>
            <p class="mt-2 text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ number_format($totals['subscriptions'], 0, ',', ' ') }} FCFA
            </p>
            <p class="mt-1 text-xs text-gray-400">{{ $totals['drivers_subscribed'] }} abonnés ce mois</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Utilisateurs</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-200">
                {{ number_format($totals['total_users'], 0, ',', ' ') }}
            </p>
            <p class="mt-1 text-xs text-gray-400">dont {{ $totals['total_drivers'] }} chauffeurs</p>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- TABLEAU MENSUEL                                        --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Revenus par mois (12 derniers mois)</h2>
                <p class="text-sm text-gray-500 mt-0.5">Rechargements wallet, réservations bus et abonnements chauffeurs</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mois</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-green-600 uppercase tracking-wide">Rechargements</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-red-500 uppercase tracking-wide">Débits</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-blue-600 uppercase tracking-wide">Réservations bus</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-yellow-600 uppercase tracking-wide">Abonnements</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Nb réservations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($monthly as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $row['label'] }}</td>
                            <td class="px-6 py-3 text-right text-green-600 font-mono">
                                {{ $row['recharges'] > 0 ? number_format($row['recharges'], 0, ',', ' ') . ' FCFA' : '—' }}
                            </td>
                            <td class="px-6 py-3 text-right text-red-500 font-mono">
                                {{ $row['debits'] > 0 ? number_format($row['debits'], 0, ',', ' ') . ' FCFA' : '—' }}
                            </td>
                            <td class="px-6 py-3 text-right text-blue-600 font-mono">
                                {{ $row['bookings_revenue'] > 0 ? number_format($row['bookings_revenue'], 0, ',', ' ') . ' FCFA' : '—' }}
                            </td>
                            <td class="px-6 py-3 text-right text-yellow-600 font-mono">
                                {{ $row['subscriptions'] > 0 ? number_format($row['subscriptions'], 0, ',', ' ') . ' FCFA' : '—' }}
                            </td>
                            <td class="px-6 py-3 text-right text-gray-500">
                                {{ $row['bookings_count'] > 0 ? $row['bookings_count'] : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 dark:bg-gray-700/70 font-semibold">
                    <tr>
                        <td class="px-6 py-3 text-gray-900 dark:text-white">Total</td>
                        <td class="px-6 py-3 text-right text-green-700 font-mono">
                            {{ number_format(collect($monthly)->sum('recharges'), 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-3 text-right text-red-600 font-mono">
                            {{ number_format(collect($monthly)->sum('debits'), 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-3 text-right text-blue-700 font-mono">
                            {{ number_format(collect($monthly)->sum('bookings_revenue'), 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-3 text-right text-yellow-700 font-mono">
                            {{ number_format(collect($monthly)->sum('subscriptions'), 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-3 text-right text-gray-600">
                            {{ collect($monthly)->sum('bookings_count') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- TOP RECHARGEURS                                        --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    @if ($topRechargers->isNotEmpty())
    <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Top 5 — Meilleurs rechargeurs</h2>
            <p class="text-sm text-gray-500 mt-0.5">Utilisateurs ayant le plus rechargé leur wallet (tous les temps)</p>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($topRechargers as $i => $recharger)
            <li class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $i === 0 ? 'bg-yellow-100 text-yellow-700' : ($i === 1 ? 'bg-gray-200 text-gray-700' : 'bg-orange-100 text-orange-700') }}">
                        {{ $i + 1 }}
                    </span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $recharger->user?->name ?? 'Utilisateur #' . $recharger->user_id }}</p>
                        <p class="text-xs text-gray-400">{{ $recharger->user?->phone ?? '' }}</p>
                    </div>
                </div>
                <span class="font-semibold text-green-600 font-mono text-sm">
                    {{ number_format($recharger->total, 0, ',', ' ') }} FCFA
                </span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

</x-filament-panels::page>
