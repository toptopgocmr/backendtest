{{-- resources/views/admin/payments/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Paiements')
@section('content')
<h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;margin-bottom:8px">Gestion des paiements</h2>
<p style="color:var(--txt3);font-size:13px;margin-bottom:20px">{{ $payments->total() }} paiements au total</p>

<!-- Revenue summary -->
<div class="stat-grid" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-icon" style="background:#ECFDF5;color:#10B981"><i class="fas fa-check-circle"></i></div>
    <div class="stat-value">{{ number_format($stats['success_amount'],0,',',' ') }}</div>
    <div class="stat-label">XAF — Paiements réussis</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FFF7ED;color:#EA580C"><i class="fas fa-clock"></i></div>
    <div class="stat-value">{{ $stats['pending_count'] }}</div>
    <div class="stat-label">Paiements en attente</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FEF2F2;color:#EF4444"><i class="fas fa-times-circle"></i></div>
    <div class="stat-value">{{ $stats['failed_count'] }}</div>
    <div class="stat-label">Paiements échoués</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--goldpal);color:var(--gold)"><i class="fas fa-undo"></i></div>
    <div class="stat-value">{{ $stats['refunded_count'] }}</div>
    <div class="stat-label">Remboursements</div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Historique des paiements</h3>
    <form method="GET" style="display:flex;gap:10px">
      <select name="method" class="form-control" style="width:160px">
        <option value="">Toutes méthodes</option>
        @foreach(['mtn_momo'=>'MTN MoMo','airtel_money'=>'Airtel Money','orange_money'=>'Orange Money','virement'=>'Virement'] as $v=>$l)
          <option value="{{ $v }}" {{ request('method')==$v?'selected':'' }}>{{ $l }}</option>
        @endforeach
      </select>
      <select name="status" class="form-control" style="width:140px">
        <option value="">Tous statuts</option>
        @foreach(['en_attente','succès','échoué','remboursé'] as $s)
          <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ $s }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-gold">Filtrer</button>
    </form>
  </div>
  <table>
    <thead><tr><th>Référence</th><th>Client</th><th>Réservation</th><th>Méthode</th><th>Montant</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($payments as $payment)
    <tr>
      <td><strong style="color:var(--navy2)">{{ $payment->reference }}</strong></td>
      <td>{{ $payment->user->name ?? '—' }}</td>
      <td><a href="{{ route('admin.bookings.show', $payment->booking->reference ?? '#') }}" style="color:var(--gold);text-decoration:none;font-weight:600">{{ $payment->booking->reference ?? '—' }}</a></td>
      <td>
        @php $icons=['mtn_momo'=>'📱','airtel_money'=>'📲','orange_money'=>'🟠','virement'=>'🏦','carte'=>'💳']; @endphp
        {{ $icons[$payment->method] ?? '💳' }} {{ $payment->method_label }}
      </td>
      <td><strong>{{ number_format($payment->amount,0,',',' ') }} {{ $payment->currency }}</strong></td>
      <td><span class="badge-status {{ $payment->status }}">{{ $payment->status }}</span></td>
      <td style="font-size:12px;color:var(--txt3)">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
      <td>
        @if($payment->isSuccess())
        <form action="{{ route('admin.payments.refund', $payment->reference) }}" method="POST" onsubmit="return confirm('Rembourser ce paiement ?')">
          @csrf
          <input type="hidden" name="reason" value="Remboursement admin">
          <button type="submit" class="btn btn-danger btn-sm">Rembourser</button>
        </form>
        @endif
      </td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--txt3)">💳 Aucun paiement</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="padding:16px 20px;border-top:1px solid var(--border)">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection
