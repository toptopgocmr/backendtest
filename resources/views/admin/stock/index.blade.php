@extends('admin.layouts.app')
@section('title', 'Gestion des Stocks')

@section('extra_css')
<style>
.stock-bar-wrap{background:#F3F4F6;border-radius:20px;height:8px;overflow:hidden;margin-top:6px;}
.stock-bar{height:100%;border-radius:20px;transition:width .3s;}
.stock-bar.ok{background:var(--green);}
.stock-bar.warning{background:#F59E0B;}
.stock-bar.critical{background:var(--coral);}
.alert-banner{background:linear-gradient(135deg,#FEF2F2,#FFF7ED);border:1.5px solid #FECACA;border-radius:14px;padding:16px 22px;margin-bottom:22px;display:flex;align-items:center;gap:14px;}
.alert-banner-critical{background:linear-gradient(135deg,#FEF2F2,#fff);border-color:#EF4444;}
.pulse{animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.5;}}
</style>
@endsection

@section('content')

<!-- Bandeau alertes critiques -->
@if($criticalCount > 0)
<div class="alert-banner alert-banner-critical">
  <div style="font-size:24px;" class="pulse">🚨</div>
  <div style="flex:1;">
    <div style="font-weight:700;color:#991B1B;font-size:15px;">{{ $criticalCount }} article(s) en rupture critique !</div>
    <div style="font-size:13px;color:#B91C1C;margin-top:2px;">Réapprovisionnement urgent nécessaire.</div>
  </div>
  <a href="{{ route('admin.stock.alerts') }}" class="btn btn-danger btn-sm">
    <i class="fas fa-exclamation-triangle"></i> Voir les alertes
  </a>
</div>
@elseif($alertsCount > 0)
<div class="alert-banner">
  <div style="font-size:22px;">⚠️</div>
  <div style="flex:1;">
    <div style="font-weight:700;color:#92400E;">{{ $alertsCount }} article(s) sous le seuil minimum</div>
    <div style="font-size:13px;color:#B45309;">Pensez à réapprovisionner prochainement.</div>
  </div>
  <a href="{{ route('admin.stock.alerts') }}" class="btn btn-sm" style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;">
    <i class="fas fa-bell"></i> Voir les alertes
  </a>
</div>
@endif

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--navy);">Gestion des Stocks</h2>
    <p style="color:var(--txt3);font-size:13px;margin-top:4px;">{{ $stats['total'] }} article(s) · {{ $stats['low'] }} sous le seuil</p>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="{{ route('admin.stock.movements') }}" class="btn btn-outline">
      <i class="fas fa-history"></i> Mouvements
    </a>
    <a href="{{ route('admin.stock.alerts') }}" class="btn btn-outline" style="{{ $alertsCount>0?'border-color:#F59E0B;color:#92400E;':'' }}">
      <i class="fas fa-bell"></i> Alertes
      @if($alertsCount > 0)<span style="background:#EF4444;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:4px;">{{ $alertsCount }}</span>@endif
    </a>
    <a href="{{ route('admin.stock.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Nouvel article
    </a>
  </div>
</div>

<!-- Stats -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:#EFF6FF;color:#3B82F6;"><i class="fas fa-boxes"></i></div>
    <div class="stat-value">{{ $stats['total'] }}</div>
    <div class="stat-label">Articles en stock</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ECFDF5;color:#10B981;"><i class="fas fa-check-circle"></i></div>
    <div class="stat-value">{{ $stats['total'] - $stats['low'] }}</div>
    <div class="stat-label">Niveaux OK</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FFF7ED;color:#F59E0B;"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="stat-value">{{ $stats['low'] }}</div>
    <div class="stat-label">Sous le seuil</div>
    @if($stats['low'] > 0)<div class="stat-change down">⚠ Attention requise</div>@endif
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FEF2F2;color:#EF4444;"><i class="fas fa-times-circle"></i></div>
    <div class="stat-value">{{ $stats['critical'] }}</div>
    <div class="stat-label">Critiques</div>
    @if($stats['critical'] > 0)<div class="stat-change down">🚨 Urgent</div>@endif
  </div>
</div>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px;">
  <div style="padding:16px 22px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
      <input type="text" name="search" class="form-control" placeholder="🔍 Nom, référence..."
             value="{{ request('search') }}" style="max-width:240px;">
      <select name="category" class="form-control" style="max-width:180px;">
        <option value="">Toutes catégories</option>
        @foreach($categories as $cat)
          <option value="{{ $cat->id }}" {{ request('category')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
        @endforeach
      </select>
      <select name="property" class="form-control" style="max-width:200px;">
        <option value="">Toutes propriétés</option>
        <option value="0" {{ request('property')==='0'?'selected':'' }}>Stock central</option>
        @foreach($properties as $p)
          <option value="{{ $p->id }}" {{ request('property')==$p->id?'selected':'' }}>{{ $p->title }}</option>
        @endforeach
      </select>
      <select name="level" class="form-control" style="max-width:160px;">
        <option value="">Tous niveaux</option>
        <option value="low" {{ request('level')=='low'?'selected':'' }}>⚠ Sous le seuil</option>
        <option value="critical" {{ request('level')=='critical'?'selected':'' }}>🚨 Critiques</option>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
      <a href="{{ route('admin.stock.index') }}" class="btn btn-outline">Réinitialiser</a>
    </form>
  </div>
</div>

<!-- Table articles -->
<div class="card">
  <div class="card-header">
    <i class="fas fa-warehouse" style="color:var(--tholad-blue);"></i>
    <h3>Inventaire</h3>
  </div>
  <table>
    <thead>
      <tr>
        <th>Article</th>
        <th>Catégorie</th>
        <th>Propriété</th>
        <th style="min-width:180px;">Niveau de stock</th>
        <th>Unité</th>
        <th>Seuil min.</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $item)
      @php $level = $item->stock_level; @endphp
      <tr style="{{ $level==='critical' ? 'background:#FFF5F5;' : ($level==='warning' ? 'background:#FFFBEB;' : '') }}">
        <td>
          <div style="font-weight:600;color:var(--navy);">
            @if($level==='critical') 🚨 @elseif($level==='warning') ⚠️ @endif
            {{ $item->name }}
          </div>
          @if($item->reference)<div style="font-size:11px;color:var(--txt3);">Réf: {{ $item->reference }}</div>@endif
        </td>
        <td>
          <span style="background:{{ $item->category->color ?? '#3B82F6' }}18;color:{{ $item->category->color ?? '#3B82F6' }};padding:3px 8px;border-radius:8px;font-size:12px;font-weight:600;">
            {{ $item->category->name ?? '—' }}
          </span>
        </td>
        <td style="font-size:13px;color:var(--txt2);">
          {{ $item->property ? $item->property->title : '🏢 Stock central' }}
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-weight:700;font-size:15px;color:{{ $level==='critical'?'#EF4444':($level==='warning'?'#F59E0B':'#10B981') }};">
              {{ number_format($item->quantity_current, 0) }}
            </span>
            <div style="flex:1;">
              <div class="stock-bar-wrap">
                <div class="stock-bar {{ $level }}" style="width:{{ $item->stock_percent }}%;"></div>
              </div>
              <div style="font-size:10px;color:var(--txt3);margin-top:2px;">{{ $item->stock_percent }}% du niveau optimal</div>
            </div>
          </div>
        </td>
        <td style="color:var(--txt3);">{{ $item->unit }}</td>
        <td style="font-size:13px;">{{ number_format($item->quantity_minimum, 0) }} {{ $item->unit }}</td>
        <td>
          @if($level === 'critical')
            <span class="badge-status annulé">🚨 Critique</span>
          @elseif($level === 'warning')
            <span style="background:#FEF3C7;color:#92400E;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;">⚠ Faible</span>
          @else
            <span class="badge-status actif">✓ OK</span>
          @endif
        </td>
        <td>
          <div style="display:flex;gap:5px;">
            <a href="{{ route('admin.stock.show', $item->id) }}" class="btn btn-sm btn-outline" title="Détails"><i class="fas fa-eye"></i></a>
            <!-- Modal rapide entrée stock -->
            <button onclick="openModal('add-{{ $item->id }}')" class="btn btn-sm btn-success" title="Ajouter stock"><i class="fas fa-plus"></i></button>
            <button onclick="openModal('rem-{{ $item->id }}')" class="btn btn-sm btn-danger" title="Retirer stock"><i class="fas fa-minus"></i></button>
          </div>

          <!-- Modal Entrée -->
          <div id="add-{{ $item->id }}" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:16px;padding:28px;width:380px;max-width:90vw;">
              <h4 style="font-family:'Cormorant Garamond',serif;font-size:18px;margin-bottom:4px;">➕ Ajouter du stock</h4>
              <p style="font-size:13px;color:var(--txt3);margin-bottom:16px;">{{ $item->name }} — Stock actuel : <strong>{{ $item->quantity_current }} {{ $item->unit }}</strong></p>
              <form method="POST" action="{{ route('admin.stock.add', $item->id) }}">
                @csrf
                <div class="form-group">
                  <label>Quantité à ajouter ({{ $item->unit }}) *</label>
                  <input type="number" name="quantity" class="form-control" required min="0.01" step="0.01" placeholder="Ex: 10">
                </div>
                <div class="form-group">
                  <label>N° bon de livraison</label>
                  <input type="text" name="reference" class="form-control" placeholder="BL-2026-...">
                </div>
                <div class="form-group">
                  <label>Motif</label>
                  <input type="text" name="reason" class="form-control" value="Réapprovisionnement">
                </div>
                <div style="display:flex;gap:10px;margin-top:6px;">
                  <button type="submit" class="btn btn-primary" style="flex:1;">Confirmer</button>
                  <button type="button" onclick="closeModal('add-{{ $item->id }}')" class="btn btn-outline">Annuler</button>
                </div>
              </form>
            </div>
          </div>

          <!-- Modal Sortie -->
          <div id="rem-{{ $item->id }}" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:16px;padding:28px;width:380px;max-width:90vw;">
              <h4 style="font-family:'Cormorant Garamond',serif;font-size:18px;margin-bottom:4px;">➖ Retirer du stock</h4>
              <p style="font-size:13px;color:var(--txt3);margin-bottom:16px;">{{ $item->name }} — Stock actuel : <strong>{{ $item->quantity_current }} {{ $item->unit }}</strong></p>
              <form method="POST" action="{{ route('admin.stock.remove', $item->id) }}">
                @csrf
                <div class="form-group">
                  <label>Quantité à retirer ({{ $item->unit }}) *</label>
                  <input type="number" name="quantity" class="form-control" required min="0.01" step="0.01" max="{{ $item->quantity_current }}" placeholder="Ex: 5">
                </div>
                <div class="form-group">
                  <label>Propriété concernée</label>
                  <select name="property_id" class="form-control">
                    <option value="">— Stock central —</option>
                    @foreach($properties as $p)
                      <option value="{{ $p->id }}">{{ $p->title }} ({{ $p->city }})</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label>Motif</label>
                  <input type="text" name="reason" class="form-control" placeholder="Utilisation, distribution...">
                </div>
                <div style="display:flex;gap:10px;margin-top:6px;">
                  <button type="submit" class="btn btn-danger" style="flex:1;">Confirmer la sortie</button>
                  <button type="button" onclick="closeModal('rem-{{ $item->id }}')" class="btn btn-outline">Annuler</button>
                </div>
              </form>
            </div>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--txt3);">
        <i class="fas fa-boxes" style="font-size:28px;margin-bottom:10px;display:block;"></i>
        Aucun article en stock
      </td></tr>
      @endforelse
    </tbody>
  </table>
  @if($items->hasPages())
  <div style="padding:16px 22px;border-top:1px solid var(--border);">{{ $items->withQueryString()->links() }}</div>
  @endif
</div>
@endsection

@section('extra_js')
<script>
function openModal(id){ document.getElementById(id).style.display='flex'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }
// Fermer au clic extérieur
document.querySelectorAll('[id^="add-"],[id^="rem-"]').forEach(m=>{
  m.addEventListener('click',e=>{ if(e.target===m) m.style.display='none'; });
});
</script>
@endsection
