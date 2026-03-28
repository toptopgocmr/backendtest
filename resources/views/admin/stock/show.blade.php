@extends('admin.layouts.app')
@section('title', 'Détail article')

@section('extra_css')
<style>
.stock-bar-wrap{background:#F3F4F6;border-radius:20px;height:14px;overflow:hidden;}
.stock-bar{height:100%;border-radius:20px;}
.stock-bar.ok{background:var(--green);}
.stock-bar.warning{background:#F59E0B;}
.stock-bar.critical{background:var(--coral);}
</style>
@endsection

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.stock.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">{{ $item->name }}</h2>
  @php $level = $item->stock_level; @endphp
  @if($level==='critical') <span class="badge-status annulé">🚨 Critique</span>
  @elseif($level==='warning') <span style="background:#FEF3C7;color:#92400E;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">⚠ Faible</span>
  @else <span class="badge-status actif">✅ OK</span>
  @endif
</div>

<div class="grid-2" style="align-items:start;">

  <!-- Gauche : infos + niveau + actions rapides -->
  <div>
    <!-- Niveau de stock -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><i class="fas fa-tachometer-alt" style="color:var(--tholad-blue);"></i><h3>Niveau de stock</h3></div>
      <div style="padding:22px;">
        <div style="text-align:center;margin-bottom:20px;">
          <div style="font-size:56px;font-weight:900;font-family:'Cormorant Garamond',serif;color:{{ $level==='critical'?'#EF4444':($level==='warning'?'#F59E0B':'#10B981') }};">
            {{ number_format($item->quantity_current, 0) }}
          </div>
          <div style="font-size:14px;color:var(--txt3);">{{ $item->unit }}(s) en stock</div>
        </div>
        <div class="stock-bar-wrap">
          <div class="stock-bar {{ $level }}" style="width:{{ $item->stock_percent }}%;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--txt3);margin-top:6px;">
          <span>0</span>
          <span>Min: {{ $item->quantity_minimum }}</span>
          <span>Optimal: {{ $item->quantity_optimal }}</span>
        </div>

        <!-- Actions rapides -->
        <div style="display:flex;gap:10px;margin-top:20px;">
          <button onclick="document.getElementById('modal-add').style.display='flex'" class="btn btn-primary" style="flex:1;">
            <i class="fas fa-plus"></i> Ajouter
          </button>
          <button onclick="document.getElementById('modal-rem').style.display='flex'" class="btn btn-danger" style="flex:1;">
            <i class="fas fa-minus"></i> Retirer
          </button>
        </div>
      </div>
    </div>

    <!-- Infos article -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-info-circle" style="color:var(--tholad-blue);"></i><h3>Informations</h3>
        <a href="{{ route('admin.stock.edit', $item->id) }}" class="btn btn-sm btn-outline" style="margin-left:auto;">
          <i class="fas fa-edit"></i> Modifier
        </a>
      </div>
      <div style="padding:22px;">
        @php
        $infos = [
          ['Catégorie', $item->category->name ?? '—'],
          ['Référence', $item->reference ?? '—'],
          ['Unité', $item->unit],
          ['Seuil minimum', $item->quantity_minimum . ' ' . $item->unit],
          ['Quantité optimale', $item->quantity_optimal . ' ' . $item->unit],
          ['Prix unitaire', $item->unit_price ? number_format($item->unit_price, 0, ',', ' ') . ' XAF' : '—'],
          ['Fournisseur', $item->supplier ?? '—'],
          ['Propriété', $item->property ? $item->property->title : 'Stock central'],
        ];
        @endphp
        @foreach($infos as [$label, $value])
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
          <span style="font-size:13px;color:var(--txt3);">{{ $label }}</span>
          <span style="font-size:13.5px;font-weight:600;color:var(--navy);">{{ $value }}</span>
        </div>
        @endforeach
        @if($item->description)
        <div style="margin-top:14px;font-size:13px;color:var(--txt2);">{{ $item->description }}</div>
        @endif
      </div>
    </div>
  </div>

  <!-- Droite : alertes + historique -->
  <div>
    <!-- Alertes actives -->
    @if($alerts->where('is_resolved', false)->count() > 0)
    <div style="background:#FEF2F2;border:1.5px solid #FECACA;border-radius:14px;padding:16px 20px;margin-bottom:20px;">
      <div style="font-weight:700;color:#991B1B;margin-bottom:10px;"><i class="fas fa-exclamation-triangle"></i> Alertes actives</div>
      @foreach($alerts->where('is_resolved', false) as $alert)
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #FECACA;">
        <div>
          <span style="font-size:12px;font-weight:700;color:{{ $alert->level==='critical'?'#EF4444':'#F59E0B' }};">
            {{ $alert->level==='critical' ? '🚨 CRITIQUE' : '⚠ FAIBLE' }}
          </span>
          <div style="font-size:11px;color:var(--txt3);">Au moment de l'alerte : {{ $alert->quantity_at_alert }} {{ $item->unit }}</div>
        </div>
        <form method="POST" action="{{ route('admin.stock.alerts.resolve', $alert->id) }}">
          @csrf @method('PUT')
          <button type="submit" class="btn btn-sm btn-success">Résoudre</button>
        </form>
      </div>
      @endforeach
    </div>
    @endif

    <!-- Historique mouvements -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-history" style="color:var(--tholad-blue);"></i>
        <h3>Historique des mouvements</h3>
        <a href="{{ route('admin.stock.movements') }}" class="btn btn-sm btn-outline" style="margin-left:auto;">Voir tout</a>
      </div>
      @if($movements->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--txt3);">
          <i class="fas fa-history" style="font-size:24px;margin-bottom:8px;display:block;"></i>
          Aucun mouvement enregistré
        </div>
      @else
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Qté</th>
            <th>Avant → Après</th>
            <th>Agent / Motif</th>
          </tr>
        </thead>
        <tbody>
          @foreach($movements as $mv)
          <tr>
            <td style="font-size:12px;color:var(--txt3);">{{ $mv->created_at->format('d/m H:i') }}</td>
            <td>
              @if($mv->type==='entrée')
                <span style="color:#10B981;font-weight:700;">➕ Entrée</span>
              @else
                <span style="color:#EF4444;font-weight:700;">➖ Sortie</span>
              @endif
            </td>
            <td style="font-weight:700;color:{{ $mv->type==='entrée'?'#10B981':'#EF4444' }};">
              {{ $mv->type==='entrée'?'+':'-' }}{{ number_format($mv->quantity, 1) }}
            </td>
            <td style="font-size:12px;">{{ number_format($mv->quantity_before,1) }} → <strong>{{ number_format($mv->quantity_after,1) }}</strong></td>
            <td style="font-size:12px;color:var(--txt2);">
              {{ $mv->agent ? $mv->agent->name : 'Admin' }}<br>
              <span style="color:var(--txt3);">{{ $mv->reason ?? '' }}</span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @if($movements->hasPages())
      <div style="padding:12px 16px;border-top:1px solid var(--border);">{{ $movements->links() }}</div>
      @endif
      @endif
    </div>
  </div>
</div>

<!-- Modal Entrée -->
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:400px;max-width:90vw;">
    <h4 style="font-family:'Cormorant Garamond',serif;font-size:20px;margin-bottom:16px;">➕ Ajouter du stock</h4>
    <form method="POST" action="{{ route('admin.stock.add', $item->id) }}">
      @csrf
      <div class="form-group"><label>Quantité ({{ $item->unit }}) *</label>
        <input type="number" name="quantity" class="form-control" required min="0.01" step="0.01" autofocus></div>
      <div class="form-group"><label>N° bon de livraison</label>
        <input type="text" name="reference" class="form-control" placeholder="BL-2026-..."></div>
      <div class="form-group"><label>Motif</label>
        <input type="text" name="reason" class="form-control" value="Réapprovisionnement"></div>
      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Confirmer</button>
        <button type="button" onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Sortie -->
<div id="modal-rem" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:400px;max-width:90vw;">
    <h4 style="font-family:'Cormorant Garamond',serif;font-size:20px;margin-bottom:16px;">➖ Retirer du stock</h4>
    <form method="POST" action="{{ route('admin.stock.remove', $item->id) }}">
      @csrf
      <div class="form-group"><label>Quantité ({{ $item->unit }}) *</label>
        <input type="number" name="quantity" class="form-control" required min="0.01" step="0.01" max="{{ $item->quantity_current }}"></div>
      <div class="form-group"><label>Motif</label>
        <input type="text" name="reason" class="form-control" placeholder="Usage, distribution..."></div>
      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-danger" style="flex:1;">Confirmer la sortie</button>
        <button type="button" onclick="document.getElementById('modal-rem').style.display='none'" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>
@endsection
