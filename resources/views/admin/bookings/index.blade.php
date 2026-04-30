@extends('admin.layouts.app')

@section('title', 'Réservations')

@section('content')
<div class="p-6">

    {{-- En-tête --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gestion des réservations</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $bookings->total() }} réservation{{ $bookings->total() > 1 ? 's' : '' }} au total</p>
        </div>
        <a href="{{ route('admin.bookings.export-csv') }}?{{ http_build_query(request()->only('status','date_from','date_to')) }}"
           class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
            </svg>
            Exporter CSV
        </a>
    </div>

    {{-- Filtres statut --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach([''=>'Toutes','en_attente'=>'En attente','confirmé'=>'Confirmées','terminé'=>'Terminées','annulé'=>'Annulées'] as $val => $label)
            <a href="{{ route('admin.bookings.index', array_merge(request()->query(), ['status' => $val ?: null])) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium border transition
                      {{ request('status', '') === $val
                         ? 'bg-indigo-600 text-white border-indigo-600'
                         : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tableau --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Référence</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Propriété</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dates / Durée</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>

                    {{-- ✅ NOUVELLE COLONNE COMMISSION --}}
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <span class="inline-flex items-center gap-1">
                            Commission
                            <span class="text-indigo-400" title="Calculée depuis le taux du propriétaire">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </span>
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paiement</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bookings as $booking)
                    @php
                        $statusColors = [
                            'confirmé'   => ['bg-green-100 text-green-700',  'bg-green-500'],
                            'en_attente' => ['bg-orange-100 text-orange-700','bg-orange-400'],
                            'terminé'    => ['bg-gray-100 text-gray-600',    'bg-gray-400'],
                            'annulé'     => ['bg-red-100 text-red-700',      'bg-red-500'],
                        ];
                        [$statusClass] = $statusColors[$booking->status] ?? ['bg-gray-100 text-gray-600', 'bg-gray-400'];

                        $paymentStatus = $booking->payment?->status ?? 'non_payé';
                        $paymentClass  = match($paymentStatus) {
                            'succès','validé' => 'text-green-600 font-semibold',
                            'en_attente'      => 'text-orange-500',
                            'remboursé'       => 'text-blue-500',
                            default           => 'text-gray-400',
                        };
                        $paymentLabel = match($paymentStatus) {
                            'succès','validé' => 'Paiement validé',
                            'en_attente'      => 'En attente',
                            'remboursé'       => 'Remboursé',
                            default           => 'Non payé',
                        };

                        // Durée
                        $pricePeriod = $booking->property?->price_period ?? 'nuit';
                        $nights      = $booking->nights ?? 0;

                        // Commission
                        $rate   = $booking->commission_rate;
                        $amount = $booking->commission_amount;
                    @endphp

                    <tr class="hover:bg-gray-50 transition-colors">

                        {{-- Référence --}}
                        <td class="px-4 py-4">
                            <a href="{{ route('admin.bookings.show', $booking->reference) }}"
                               class="font-mono font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                                {{ $booking->reference }}
                            </a>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $booking->created_at?->format('d/m/Y') }}</div>
                        </td>

                        {{-- Client --}}
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($booking->user?->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800">{{ $booking->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $booking->user?->phone ?? '' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Propriété --}}
                        <td class="px-4 py-4">
                            <div class="font-medium text-gray-700 max-w-[160px] truncate" title="{{ $booking->property?->title }}">
                                {{ Str::limit($booking->property?->title ?? '—', 28) }}
                            </div>
                            @if($booking->property?->owner)
                                <div class="text-xs text-gray-400 mt-0.5 truncate">
                                    {{ $booking->property->owner->name }}
                                </div>
                            @endif
                        </td>

                        {{-- Dates / Durée --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1 text-gray-700">
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $booking->check_in?->format('d/m/Y') }}
                            </div>
                            <div class="flex items-center gap-1 text-gray-700 mt-0.5">
                                <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $booking->check_out?->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ $nights }}
                                @if($pricePeriod === 'heure')    {{ $nights <= 1 ? 'heure' : 'heures' }}
                                @elseif($pricePeriod === 'jour')  {{ $nights <= 1 ? 'jour' : 'jours' }}
                                @elseif($pricePeriod === 'mois')  {{ $nights <= 1 ? 'mois' : 'mois' }}
                                @else                             {{ $nights <= 1 ? 'nuit' : 'nuits' }}
                                @endif
                            </div>
                        </td>

                        {{-- Montant --}}
                        <td class="px-4 py-4 text-right">
                            <div class="font-bold text-gray-800">
                                {{ number_format($booking->total_amount, 0, ',', ' ') }}
                                <span class="text-xs font-normal text-gray-400">{{ $booking->currency ?? 'XAF' }}</span>
                            </div>
                        </td>

                        {{-- ✅ COMMISSION THOLAD --}}
                        <td class="px-4 py-4 text-right">
                            @if($rate > 0)
                                <div class="font-semibold text-indigo-700">
                                    {{ number_format($amount, 0, ',', ' ') }}
                                    <span class="text-xs font-normal text-gray-400">{{ $booking->currency ?? 'XAF' }}</span>
                                </div>
                                <div class="inline-flex items-center mt-0.5 px-1.5 py-0.5 bg-indigo-50 text-indigo-500 rounded text-xs font-medium">
                                    {{ number_format($rate, $rate == intval($rate) ? 0 : 1) }}%
                                </div>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Paiement --}}
                        <td class="px-4 py-4">
                            <span class="text-sm {{ $paymentClass }}">{{ $paymentLabel }}</span>
                        </td>

                        {{-- Statut --}}
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                {{ $booking->status_label }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Voir --}}
                                <a href="{{ route('admin.bookings.show', $booking->reference) }}"
                                   class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                   title="Voir le détail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                {{-- Annuler (en_attente ou confirmé uniquement) --}}
                                @if(in_array($booking->status, ['en_attente','confirmé']))
                                    <form method="POST"
                                          action="{{ route('admin.bookings.cancel', $booking->reference) }}"
                                          onsubmit="return confirm('Annuler la réservation {{ $booking->reference }} ?')"
                                          class="inline">
                                        @csrf @method('PUT')
                                        <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                                title="Annuler">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="font-medium">Aucune réservation trouvée</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $bookings->withQueryString()->links() }}
    </div>

</div>
@endsection
