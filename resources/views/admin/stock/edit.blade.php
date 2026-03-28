@extends('admin.layouts.app')
@section('title', 'Modifier article — ' . $item->name)

@section('extra_css')
<style>
.stock-bar-wrap{background:#F3F4F6;border-radius:20px;height:12px;overflow:hidden;}
.stock-bar{height:100%;border-radius:20px;transition:width .3s;}
</style>
@endsection

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.stock.show', $item->id) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">
    Modifier — {{ $item->name }}
  </h2>
</div>

<form method="POST" action="{{ route('admin.stock.update', $item->id) }}">
@csrf @method('PUT')

<div class="grid-2" style="align-items:start;">

  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><i class="fas fa-box" style="color:var(--tholad-blue);"></i><h3>Informations article</h3></div>
      <div style="padding:22px;">
        @if($errors->any())
          <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
            <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          </div>
        @endif

        <div class="form-group">
          <label>Nom de l'article *</label>
          <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Catégorie *</label>
            <select name="category_id" class="form-control" required>
              <option value="">Choisir...</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Référence interne</label>
            <input type="text" name="reference" class="form-control" value="{{ old('reference', $item->reference) }}">
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="2">{{ old('description', $item->description) }}</textarea>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Unité de mesure *</label>
            <select name="unit" class="form-control" required>
              @foreach(['unité','rouleau','paquet','litre','kg','boîte','carton','sac','set'] as $u)
                <option value="{{ $u }}" {{ old('unit', $item->unit) === $u ? 'selected' : '' }}>{{ ucfirst($u) }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Fournisseur</label>
            <input type="text" name="supplier" class="form-control" value="{{ old('supplier', $item->supplier) }}">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="fas fa-tag" style="color:var(--tholad-blue);"></i><h3>Prix & affectation</h3></div>
      <div style="padding:22px;">
        <div class="grid-2">
          <div class="form-group">
            <label>Prix unitaire (XAF)</label>
            <input type="number" name="unit_price" class="form-control" value="{{ old('unit_price', $item->unit_price) }}" min="0" step="0.01">
          </div>
          <div class="form-group">
            <label>Propriété concernée</label>
            <select name="property_id" class="form-control">
              <option value="">Stock central (global)</option>
              @foreach($properties as $p)
                <option value="{{ $p->id }}" {{ old('property_id', $item->property_id) == $p->id ? 'selected' : '' }}>
                  {{ $p->title }} — {{ $p->city }}
                </option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $item->is_active) ? 'checked' : '' }}
                   style="width:16px;height:16px;accent-color:var(--tholad-blue);">
            <span style="font-size:13.5px;">Article actif (visible dans le stock)</span>
          </label>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><i class="fas fa-tachometer-alt" style="color:var(--tholad-blue);"></i><h3>Seuils & quantités</h3></div>
      <div style="padding:22px;">
        <div style="background:#EFF6FF;border-radius:10px;padding:14px;margin-bottom:18px;font-size:13px;color:#1E40AF;">
          <i class="fas fa-info-circle"></i>
          <strong>Comment fonctionnent les seuils ?</strong><br>
          <span style="opacity:.85;">
            • <strong>Seuil minimum</strong> : déclenche une alerte "Faible"<br>
            • <strong>50% du seuil min</strong> : déclenche une alerte "Critique"<br>
            • <strong>Quantité optimale</strong> : objectif de réapprovisionnement
          </span>
        </div>

        <div class="form-group">
          <label>Quantité actuelle *</label>
          <input type="number" name="quantity_current" id="qty_cur" class="form-control"
                 value="{{ old('quantity_current', $item->quantity_current) }}" required min="0" step="0.01">
          <small style="color:var(--txt3);font-size:12px;">⚠️ Modifiez directement si correction d'inventaire. Pour entrée/sortie normale, utilisez les boutons + / - depuis la fiche.</small>
        </div>
        <div class="form-group">
          <label>Seuil minimum (alerte) *</label>
          <input type="number" name="quantity_minimum" id="qty_min" class="form-control"
                 value="{{ old('quantity_minimum', $item->quantity_minimum) }}" required min="0" step="0.01">
        </div>
        <div class="form-group">
          <label>Quantité optimale (objectif) *</label>
          <input type="number" name="quantity_optimal" id="qty_opt" class="form-control"
                 value="{{ old('quantity_optimal', $item->quantity_optimal) }}" required min="0" step="0.01">
        </div>

        <!-- Prévisualisation -->
        <div style="background:var(--bg-soft);border-radius:10px;padding:14px;margin-top:8px;">
          <div style="font-size:12px;font-weight:700;color:var(--txt2);margin-bottom:8px;">Prévisualisation du niveau</div>
          <div class="stock-bar-wrap">
            <div id="preview-bar" class="stock-bar" style="width:{{ $item->stock_percent }}%;background:{{ $item->stock_level === 'critical' ? '#EF4444' : ($item->stock_level === 'warning' ? '#F59E0B' : '#10B981') }};"></div>
          </div>
          <div id="preview-text" style="font-size:12px;color:var(--txt3);margin-top:6px;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div style="display:flex;gap:12px;margin-top:20px;">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
  <a href="{{ route('admin.stock.show', $item->id) }}" class="btn btn-outline">Annuler</a>
</div>
</form>
@endsection

@section('extra_js')
<script>
function updatePreview() {
  const cur = parseFloat(document.getElementById('qty_cur').value) || 0;
  const min = parseFloat(document.getElementById('qty_min').value) || 1;
  const opt = parseFloat(document.getElementById('qty_opt').value) || 1;
  const pct = Math.min(100, Math.round((cur / opt) * 100));
  const bar = document.getElementById('preview-bar');
  const txt = document.getElementById('preview-text');
  bar.style.width = pct + '%';
  if (cur <= min * 0.5) {
    bar.style.background = '#EF4444';
    txt.textContent = '🚨 Critique — Réapprovisionnement urgent';
    txt.style.color = '#EF4444';
  } else if (cur <= min) {
    bar.style.background = '#F59E0B';
    txt.textContent = '⚠️ Faible — Alerte activée';
    txt.style.color = '#F59E0B';
  } else {
    bar.style.background = '#10B981';
    txt.textContent = '✅ OK — Niveau suffisant (' + pct + '% de l\'objectif)';
    txt.style.color = '#10B981';
  }
}
document.querySelectorAll('#qty_cur, #qty_min, #qty_opt').forEach(el => el.addEventListener('input', updatePreview));
updatePreview();
</script>
@endsection
