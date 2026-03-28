@extends('admin.layouts.app')
@section('title', 'Nouvel article')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.stock.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">Ajouter un article au stock</h2>
</div>

<form method="POST" action="{{ route('admin.stock.store') }}">
@csrf
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
          <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ex: Papier toilette, Savon liquide...">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Catégorie *</label>
            <select name="category_id" class="form-control" required>
              <option value="">Choisir...</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ old('category_id')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Référence interne</label>
            <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="ART-001">
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Description, marque, spécifications...">{{ old('description') }}</textarea>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Unité de mesure *</label>
            <select name="unit" class="form-control" required>
              <option value="unité" {{ old('unit','unité')==='unité'?'selected':'' }}>Unité</option>
              <option value="rouleau" {{ old('unit')==='rouleau'?'selected':'' }}>Rouleau</option>
              <option value="paquet" {{ old('unit')==='paquet'?'selected':'' }}>Paquet</option>
              <option value="litre" {{ old('unit')==='litre'?'selected':'' }}>Litre</option>
              <option value="kg" {{ old('unit')==='kg'?'selected':'' }}>Kilogramme</option>
              <option value="boîte" {{ old('unit')==='boîte'?'selected':'' }}>Boîte</option>
              <option value="carton" {{ old('unit')==='carton'?'selected':'' }}>Carton</option>
              <option value="sac" {{ old('unit')==='sac'?'selected':'' }}>Sac</option>
            </select>
          </div>
          <div class="form-group">
            <label>Fournisseur</label>
            <input type="text" name="supplier" class="form-control" value="{{ old('supplier') }}" placeholder="Nom du fournisseur">
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
            <input type="number" name="unit_price" class="form-control" value="{{ old('unit_price') }}" min="0" step="0.01" placeholder="0">
          </div>
          <div class="form-group">
            <label>Propriété concernée</label>
            <select name="property_id" class="form-control">
              <option value="">Stock central (global)</option>
              @foreach($properties as $p)
                <option value="{{ $p->id }}" {{ old('property_id')==$p->id?'selected':'' }}>{{ $p->title }} — {{ $p->city }}</option>
              @endforeach
            </select>
          </div>
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
          <input type="number" name="quantity_current" class="form-control" value="{{ old('quantity_current', 0) }}" required min="0" step="0.01">
        </div>
        <div class="form-group">
          <label>Seuil minimum (alerte) *</label>
          <input type="number" name="quantity_minimum" class="form-control" value="{{ old('quantity_minimum', 5) }}" required min="0" step="0.01">
          <small style="color:var(--txt3);font-size:12px;">En dessous de ce seuil → alerte générée</small>
        </div>
        <div class="form-group">
          <label>Quantité optimale (objectif) *</label>
          <input type="number" name="quantity_optimal" class="form-control" value="{{ old('quantity_optimal', 20) }}" required min="0" step="0.01">
          <small style="color:var(--txt3);font-size:12px;">Quantité cible lors du réapprovisionnement</small>
        </div>

        <!-- Prévisualisation dynamique -->
        <div style="background:var(--bg-soft);border-radius:10px;padding:14px;margin-top:8px;" id="preview">
          <div style="font-size:12px;font-weight:700;color:var(--txt2);margin-bottom:8px;">Prévisualisation du niveau</div>
          <div style="background:#E5E7EB;border-radius:20px;height:12px;overflow:hidden;">
            <div id="preview-bar" style="height:100%;border-radius:20px;background:#10B981;width:0%;transition:width .3s;"></div>
          </div>
          <div id="preview-text" style="font-size:12px;color:var(--txt3);margin-top:6px;"></div>
        </div>
      </div>
    </div>

    <!-- Exemples articles communs -->
    <div style="margin-top:16px;background:var(--bg-soft);border-radius:12px;padding:16px;border:1px solid var(--border);">
      <div style="font-size:12px;font-weight:700;color:var(--txt2);margin-bottom:10px;">Articles courants (cliquez pour remplir)</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @php
        $presets = [
          ['Papier toilette','rouleau',5,50,200],['Savon liquide','litre',2,20,80],
          ['Gel douche','unité',3,15,60],['Shampoing','unité',3,12,50],
          ['Serviettes','unité',2,20,100],['Sac poubelle','carton',2,10,40],
          ['Liquide vaisselle','litre',2,15,60],['Papier essuie-mains','rouleau',3,30,120],
          ['Désinfectant','litre',2,10,40],['Ampoules','unité',2,20,80],
        ];
        @endphp
        @foreach($presets as $p)
        <button type="button" onclick="fillPreset('{{ $p[0] }}','{{ $p[1] }}',{{ $p[2] }},{{ $p[3] }},{{ $p[4] }})"
          style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:5px 10px;font-size:12px;cursor:pointer;transition:.2s;"
          onmouseover="this.style.borderColor='var(--tholad-blue)'" onmouseout="this.style.borderColor='var(--border)'">
          {{ $p[0] }}
        </button>
        @endforeach
      </div>
    </div>
  </div>
</div>

<div style="display:flex;gap:12px;margin-top:20px;">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer l'article</button>
  <a href="{{ route('admin.stock.index') }}" class="btn btn-outline">Annuler</a>
</div>
</form>
@endsection

@section('extra_js')
<script>
function fillPreset(name, unit, min, qty, optimal) {
  document.querySelector('[name=name]').value = name;
  document.querySelector('[name=unit]').value = unit;
  document.querySelector('[name=quantity_minimum]').value = min;
  document.querySelector('[name=quantity_current]').value = qty;
  document.querySelector('[name=quantity_optimal]').value = optimal;
  updatePreview();
}

function updatePreview() {
  const cur = parseFloat(document.querySelector('[name=quantity_current]').value) || 0;
  const min = parseFloat(document.querySelector('[name=quantity_minimum]').value) || 1;
  const opt = parseFloat(document.querySelector('[name=quantity_optimal]').value) || 1;
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

document.querySelectorAll('[name=quantity_current],[name=quantity_minimum],[name=quantity_optimal]')
  .forEach(el => el.addEventListener('input', updatePreview));
updatePreview();
</script>
@endsection
