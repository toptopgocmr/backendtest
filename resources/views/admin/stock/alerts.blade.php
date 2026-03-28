@extends('admin.layouts.app')
@section('title', 'Alertes Stock')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.stock.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--navy);">Alertes de Stock</h2>
    <p style="color:var(--txt3);font-size:13px;margin-top:4px;">{{ $alerts->total() }} alerte(s) active(s)</p>
  </div>
</div>

@if($alerts->isEmpty())
<div style="text-align:center;padding:60px 20px;background:#ECFDF5;border-radius:16px;border:1.5px solid #A7F3D0;">
  <div style="font-size:48px;margin-bottom:12px;">✅</div>
  <div style="font-size:18px;font-weight:700;color:#065F46;margin-bottom:6px;">Tous les stocks sont OK !</div>
  <div style="color:#047857;font-size:13px;">Aucune alerte active en ce moment.</div>
  <a href="{{ route('admin.stock.index') }}" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">
    <i class="fas fa-arrow-left"></i> Retour au stock
  </a>
</div>
@else
<div style="display:flex;flex-direction:column;gap:14px;">
  @foreach($alerts as $alert)
  @php
    $item = $alert->stockItem;
    $isCritical = $alert->level === 'critical';
  @endphp
  <div style="background:{{ $isCritical ? '#FEF2F2' : '#FFFBEB' }};border:1.5px solid {{ $isCritical ? '#FECACA' : '#FDE68A' }};border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:16px;">
    <div style="font-size:28px;">{{ $isCritical ? '🚨' : '⚠️' }}</div>
    <div style="flex:1;">
      <div style="font-weight:700;font-size:15px;color:{{ $isCritical ? '#991B1B' : '#92400E' }};">
        {{ $item->name ?? 'Article supprimé' }}
        <span style="font-size:11px;font-weight:400;background:{{ $isCritical?'#FEE2E2':'#FEF3C7' }};padding:2px 8px;border-radius:8px;margin-left:8px;">
          {{ $isCritical ? 'CRITIQUE' : 'FAIBLE' }}
        </span>
      </div>
      <div style="font-size:13px;color:var(--txt2);margin-top:4px;">
        Stock au moment de l'alerte : <strong>{{ $alert->quantity_at_alert }} {{ $item->unit ?? '' }}</strong>
        · Seuil minimum : <strong>{{ $item->quantity_minimum ?? '—' }} {{ $item->unit ?? '' }}</strong>
        @if($item && $item->property)
          · Propriété : <strong>{{ $item->property->title }}</strong>
        @else
          · <strong>Stock central</strong>
        @endif
      </div>
      <div style="font-size:11px;color:var(--txt3);margin-top:4px;">Alerte créée le {{ $alert->created_at->format('d/m/Y à H:i') }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0;">
      @if($item)
      <a href="{{ route('admin.stock.show', $item->id) }}" class="btn btn-sm btn-outline">
        <i class="fas fa-eye"></i> Voir
      </a>
      @endif
      <form method="POST" action="{{ route('admin.stock.alerts.resolve', $alert->id) }}">
        @csrf @method('PUT')
        <button type="submit" class="btn btn-sm btn-success">
          <i class="fas fa-check"></i> Résoudre
        </button>
      </form>
    </div>
  </div>
  @endforeach
</div>

@if($alerts->hasPages())
<div style="margin-top:20px;">{{ $alerts->links() }}</div>
@endif
@endif
@endsection
