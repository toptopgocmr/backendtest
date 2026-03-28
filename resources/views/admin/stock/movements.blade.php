@extends('admin.layouts.app')
@section('title', 'Mouvements de Stock')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.stock.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">Historique des Mouvements</h2>
</div>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px;">
  <div style="padding:16px 22px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
      <select name="type" class="form-control" style="max-width:180px;">
        <option value="">Tous les types</option>
        <option value="entrée" {{ request('type')==='entrée'?'selected':'' }}>➕ Entrée</option>
        <option value="sortie" {{ request('type')==='sortie'?'selected':'' }}>➖ Sortie</option>
        <option value="inventaire" {{ request('type')==='inventaire'?'selected':'' }}>📋 Inventaire</option>
        <option value="perte" {{ request('type')==='perte'?'selected':'' }}>🗑 Perte</option>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
      <a href="{{ route('admin.stock.movements') }}" class="btn btn-outline">Réinitialiser</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <i class="fas fa-history" style="color:var(--tholad-blue);"></i>
    <h3>Journal des mouvements</h3>
  </div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Article</th>
        <th>Type</th>
        <th>Quantité</th>
        <th>Avant → Après</th>
        <th>Propriété</th>
        <th>Agent</th>
        <th>Motif</th>
        <th>Réf.</th>
      </tr>
    </thead>
    <tbody>
      @forelse($movements as $mv)
      @php
        $typeConf = [
          'entrée'     => ['color'=>'#10B981','bg'=>'#ECFDF5','icon'=>'fa-arrow-down','label'=>'Entrée'],
          'sortie'     => ['color'=>'#EF4444','bg'=>'#FEF2F2','icon'=>'fa-arrow-up','label'=>'Sortie'],
          'inventaire' => ['color'=>'#3B82F6','bg'=>'#EFF6FF','icon'=>'fa-clipboard-list','label'=>'Inventaire'],
          'transfert'  => ['color'=>'#7C3AED','bg'=>'#F3E8FF','icon'=>'fa-exchange-alt','label'=>'Transfert'],
          'perte'      => ['color'=>'#6B7280','bg'=>'#F3F4F6','icon'=>'fa-trash','label'=>'Perte'],
        ];
        $tc = $typeConf[$mv->type] ?? $typeConf['sortie'];
      @endphp
      <tr>
        <td style="font-size:12px;color:var(--txt3);white-space:nowrap;">
          {{ $mv->created_at->format('d/m/Y') }}<br>
          <span style="font-size:11px;">{{ $mv->created_at->format('H:i') }}</span>
        </td>
        <td>
          <div style="font-weight:600;color:var(--navy);">{{ $mv->stockItem->name ?? '—' }}</div>
          @if($mv->stockItem && $mv->stockItem->category)
            <div style="font-size:11px;color:var(--txt3);">{{ $mv->stockItem->category->name }}</div>
          @endif
        </td>
        <td>
          <span style="background:{{ $tc['bg'] }};color:{{ $tc['color'] }};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:5px;">
            <i class="fas {{ $tc['icon'] }}"></i> {{ $tc['label'] }}
          </span>
        </td>
        <td style="font-weight:700;font-size:15px;color:{{ $mv->type==='entrée'?'#10B981':'#EF4444' }};">
          {{ $mv->type==='entrée' ? '+' : '-' }}{{ number_format($mv->quantity, 1) }}
          <span style="font-size:11px;color:var(--txt3);font-weight:400;">{{ $mv->stockItem->unit ?? '' }}</span>
        </td>
        <td style="font-size:13px;">
          <span style="color:var(--txt2);">{{ number_format($mv->quantity_before, 1) }}</span>
          <span style="color:var(--txt3);margin:0 4px;">→</span>
          <span style="font-weight:700;color:var(--navy);">{{ number_format($mv->quantity_after, 1) }}</span>
        </td>
        <td style="font-size:13px;color:var(--txt2);">
          {{ $mv->property ? $mv->property->title : '🏢 Central' }}
        </td>
        <td>
          @if($mv->agent)
            <div style="font-size:13px;font-weight:600;">{{ $mv->agent->name }}</div>
            <div style="font-size:11px;color:var(--txt3);">{{ $mv->agent->role_label }}</div>
          @else
            <span style="color:var(--txt3);font-size:12px;">Admin</span>
          @endif
        </td>
        <td style="font-size:13px;color:var(--txt2);max-width:150px;">{{ $mv->reason ?? '—' }}</td>
        <td style="font-size:12px;color:var(--txt3);">{{ $mv->reference ?? '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--txt3);">
        <i class="fas fa-history" style="font-size:28px;margin-bottom:10px;display:block;"></i>
        Aucun mouvement enregistré
      </td></tr>
      @endforelse
    </tbody>
  </table>
  @if($movements->hasPages())
  <div style="padding:16px 22px;border-top:1px solid var(--border);">{{ $movements->withQueryString()->links() }}</div>
  @endif
</div>
@endsection
