{{-- resources/views/admin/accounting/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Comptabilité')
@section('content')
<h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;margin-bottom:20px">Comptabilité</h2>

<div class="stat-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon" style="background:#ECFDF5;color:#10B981"><i class="fas fa-arrow-up"></i></div>
    <div class="stat-value">{{ number_format($summary['total_revenue'],0,',',' ') }}</div>
    <div class="stat-label">XAF — Revenus</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--goldpal);color:var(--gold)"><i class="fas fa-percentage"></i></div>
    <div class="stat-value">{{ number_format($summary['total_commission'],0,',',' ') }}</div>
    <div class="stat-label">XAF — Commissions</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FEF2F2;color:#EF4444"><i class="fas fa-arrow-down"></i></div>
    <div class="stat-value">{{ number_format($summary['total_expenses'],0,',',' ') }}</div>
    <div class="stat-label">XAF — Dépenses</div>
  </div>
  <div class="stat-card" style="border-color:var(--gold)">
    <div class="stat-icon" style="background:var(--goldpal);color:var(--gold)"><i class="fas fa-balance-scale"></i></div>
    <div class="stat-value" style="color:{{ $summary['net'] >= 0 ? 'var(--green)' : 'var(--coral)' }}">{{ number_format($summary['net'],0,',',' ') }}</div>
    <div class="stat-label">XAF — Bilan net</div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Transactions</h3>
    <form method="GET" style="display:flex;gap:10px">
      <select name="type" class="form-control" style="width:140px">
        <option value="">Tous types</option>
        @foreach(['revenue'=>'Revenus','expense'=>'Dépenses','commission'=>'Commissions','refund'=>'Remboursements'] as $v=>$l)
          <option value="{{ $v }}" {{ request('type')==$v?'selected':'' }}>{{ $l }}</option>
        @endforeach
      </select>
      <input type="month" name="month" class="form-control" style="width:160px" value="{{ request('month') }}">
      <button type="submit" class="btn btn-gold">Filtrer</button>
    </form>
  </div>
  <table>
    <thead><tr><th>Date</th><th>Type</th><th>Catégorie</th><th>Description</th><th>Référence</th><th>Montant</th></tr></thead>
    <tbody>
    @forelse($transactions as $t)
    <tr>
      <td style="font-size:12px">{{ $t->date->format('d/m/Y') }}</td>
      <td>
        @php $typeColors=['revenue'=>'var(--green)','commission'=>'var(--gold)','expense'=>'var(--coral)','refund'=>'var(--blue)']; @endphp
        <span style="color:{{ $typeColors[$t->type] ?? '#666' }};font-weight:700;font-size:12px;text-transform:uppercase">{{ $t->type }}</span>
      </td>
      <td>{{ $t->category }}</td>
      <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $t->description }}</td>
      <td style="font-size:12px;color:var(--txt3)">{{ $t->reference ?? '—' }}</td>
      <td>
        <strong style="color:{{ in_array($t->type,['revenue','commission']) ? 'var(--green)' : 'var(--coral)' }}">
          {{ in_array($t->type,['revenue','commission']) ? '+' : '-' }}{{ number_format($t->amount,0,',',' ') }} {{ $t->currency }}
        </strong>
      </td>
    </tr>
    @empty
    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--txt3)">Aucune transaction</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="padding:16px 20px;border-top:1px solid var(--border)">{{ $transactions->withQueryString()->links() }}</div>
</div>
@endsection
