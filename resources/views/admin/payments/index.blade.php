{{-- resources/views/admin/payments/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Paiements')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700">
      Gestion des paiements
    </h2>
    <p style="color:var(--txt3);font-size:13px">{{ $payments->total() }} paiements au total</p>
  </div>
  {{-- ✅ NOUVEAU : Bouton export CSV (hérite des filtres actifs) --}}
  <a href="{{ route('admin.payments.export-csv', request()->query()) }}"
     class="btn btn-outline"
     title="Exporter en CSV (Excel)">
    <i class="fas fa-file-csv"></i> Exporter CSV
  </a>
</div>

<!-- STATS -->
<div class="stat-grid" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-icon" style="background:#ECFDF5;color:#10B981"><i class="fas fa-check-circle"></i></div>
    <div class="stat-value">{{ number_format($stats['success_amount'],0,',',' ') }}</div>
    <div class="stat-label">XAF — Paiements validés</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FFF7ED;color:#EA580C"><i class="fas fa-clock"></i></div>
    <div class="stat-value">{{ $stats['pending_count'] }}</div>
    <div class="stat-label">En attente</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FEF2F2;color:#EF4444"><i class="fas fa-times-circle"></i></div>
    <div class="stat-value">{{ $stats['failed_count'] }}</div>
    <div class="stat-label">Refusés</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--goldpal);color:var(--gold)"><i class="fas fa-undo"></i></div>
    <div class="stat-value">{{ $stats['refunded_count'] }}</div>
    <div class="stat-label">Remboursés</div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Historique des paiements</h3>

    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
      <select name="method" class="form-control" style="width:160px">
        <option value="">Toutes méthodes</option>
        @foreach(['mtn_momo'=>'MTN MoMo','airtel_money'=>'Airtel Money','orange_money'=>'Orange Money','virement'=>'Virement'] as $v=>$l)
          <option value="{{ $v }}" {{ request('method')==$v?'selected':'' }}>{{ $l }}</option>
        @endforeach
      </select>

      <select name="status" class="form-control" style="width:180px">
        <option value="">Tous statuts</option>
        @foreach([
          'en_attente'               => 'En attente',
          'en_attente_confirmation'  => 'Attente confirmation',
          'succès'                   => 'Validé',
          'échoué'                   => 'Refusé',
          'remboursé'                => 'Remboursé',
        ] as $v=>$l)
          <option value="{{ $v }}" {{ request('status')==$v?'selected':'' }}>{{ $l }}</option>
        @endforeach
      </select>

      <input type="date" name="date_from" class="form-control" style="width:140px"
             value="{{ request('date_from') }}" placeholder="Du">
      <input type="date" name="date_to"   class="form-control" style="width:140px"
             value="{{ request('date_to') }}"   placeholder="Au">

      <button type="submit" class="btn btn-gold">Filtrer</button>
    </form>
  </div>

  <div style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th>Référence</th>
        <th>Client</th>
        <th>Réservation</th>
        <th>Méthode</th>
        <th>Tél. utilisé</th>
        <th>Montant</th>
        <th>ID Transaction</th>
        <th>Preuve</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>

    <tbody>
    @forelse($payments as $payment)
    <tr>

      <td>
        <strong style="color:var(--navy2);font-size:12px">
          {{ $payment->reference ?? "PAY-{$payment->id}" }}
        </strong>
      </td>

      <td>
        <div>{{ $payment->user->name ?? '—' }}</div>
        <div style="font-size:11px;color:var(--txt3)">{{ $payment->user->phone ?? '' }}</div>
      </td>

      <td>
        @if($payment->booking)
          <a href="{{ route('admin.bookings.show', $payment->booking->reference) }}"
             style="color:var(--gold);font-weight:600">
            {{ $payment->booking->reference }}
          </a>
          <div style="font-size:11px;color:var(--txt3)">
            {{ $payment->booking->property?->title ?? '—' }}
          </div>
        @else
          —
        @endif
      </td>

      <td>{{ $payment->method_emoji ?? '' }} {{ $payment->method_label ?? $payment->method }}</td>

      <td style="font-size:13px">{{ $payment->phone ?? '—' }}</td>

      <td>
        <strong>{{ $payment->formatted_amount }}</strong>
        @if($payment->booking)
          <div style="font-size:11px;color:var(--txt3)">
            Base : {{ number_format($payment->booking->base_amount,0,',',' ') }} XAF
            + Frais : {{ number_format($payment->booking->fees_amount,0,',',' ') }} XAF
          </div>
        @endif
      </td>

      <td style="font-size:12px;font-family:monospace">
        {{ $payment->provider_ref ?? '—' }}
      </td>

      <td>
        @if($payment->proof_image_url)
          <a href="{{ $payment->proof_image_url }}" target="_blank" title="Voir la preuve de paiement">
            <img src="{{ $payment->proof_image_url }}"
                 width="70"
                 style="border-radius:6px;border:2px solid #10B981;cursor:pointer"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
            <span style="display:none;font-size:11px;color:#10B981">📎 Voir</span>
          </a>
        @else
          <span style="color:var(--txt3);font-size:12px">Aucune</span>
        @endif
      </td>

      <td>
        {{--
          ✅ FIX Bug 3 : correspondance CSS sans accents → classe CSS valide
          Les valeurs DB ont des accents ('succès', 'échoué', 'remboursé').
          On les convertit en classe CSS ASCII.
        --}}
        @php
          $statusClass = match($payment->status) {
            'succès'                  => 'succes',
            'échoué'                  => 'echoue',
            'remboursé'               => 'rembourse',
            'en_attente_confirmation' => 'en_attente_confirmation',
            default                   => 'en_attente',
          };
        @endphp
        <span class="badge-status {{ $statusClass }}"
              style="white-space:nowrap;font-size:12px">
          {{ $payment->status_label ?? $payment->status }}
        </span>
        @if($payment->admin_note)
          <div style="font-size:11px;color:var(--txt3);margin-top:4px">
            {{ Str::limit($payment->admin_note, 40) }}
          </div>
        @endif
      </td>

      <td style="font-size:12px;color:var(--txt3)">
        {{ $payment->created_at->format('d/m/Y H:i') }}
        @if($payment->paid_at)
          <div style="color:#10B981;font-size:11px">
            ✅ {{ $payment->paid_at->format('d/m/Y H:i') }}
          </div>
        @endif
      </td>

      <td style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">

        {{-- ✅ VALIDER --}}
        @if(in_array($payment->status, ['en_attente','en_attente_confirmation']))
        <form method="POST" action="{{ route('admin.payments.validate', $payment->id) }}">
          @csrf
          <button class="btn btn-success btn-sm" title="Valider ce paiement">✔</button>
        </form>

        {{-- ❌ REFUSER --}}
        <form method="POST" action="{{ route('admin.payments.reject', $payment->id) }}">
          @csrf
          <input type="hidden" name="reason" value="">
          <button type="button" class="btn btn-danger btn-sm" title="Refuser ce paiement"
                  onclick="promptReject(this.form)">✖</button>
        </form>
        @endif

        {{-- 💸 REMBOURSER --}}
        @if($payment->status === 'succès')
        <form action="{{ route('admin.payments.refund', $payment->reference) }}" method="POST"
              onsubmit="return confirm('Confirmer le remboursement de ce paiement ?')">
          @csrf
          <input type="hidden" name="reason" value="Remboursement demandé">
          <button type="submit" class="btn btn-warning btn-sm" title="Rembourser">↩</button>
        </form>
        @endif

        {{-- 🖨️ REÇU DÉFINITIF --}}
        {{-- ✅ NOUVEAU : bouton "Imprimer reçu" disponible dès validation --}}
        @if($payment->status === 'succès')
        <a href="{{ route('admin.payments.receipt', $payment->id) }}"
           target="_blank"
           class="btn btn-outline btn-sm"
           title="Imprimer le reçu définitif">
          <i class="fas fa-print"></i>
        </a>
        @endif

      </td>

    </tr>
    @empty
    <tr>
      <td colspan="11" style="text-align:center;padding:40px;color:var(--txt3)">
        💳 Aucun paiement trouvé
      </td>
    </tr>
    @endforelse
    </tbody>
  </table>
  </div>

  <div style="padding:16px 20px;border-top:1px solid var(--border)">
    {{ $payments->withQueryString()->links() }}
  </div>
</div>

<script>
function promptReject(form) {
  const reason = prompt('Motif du refus (optionnel) :');
  if (reason === null) return; // annulé
  form.querySelector('[name="reason"]').value = reason;
  form.submit();
}
</script>
@endsection
