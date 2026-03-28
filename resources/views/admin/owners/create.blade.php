@extends('admin.layouts.app')
@section('title', 'Nouveau propriétaire')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.owners.index') }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i>
  </a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">
    Enregistrer un propriétaire
  </h2>
</div>

<form method="POST" action="{{ route('admin.owners.store') }}">
@csrf

<div class="grid-2" style="align-items:start;">

  <!-- Colonne gauche : Infos personnelles -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-user" style="color:var(--tholad-blue);"></i>
        <h3>Informations personnelles</h3>
      </div>
      <div style="padding:22px;">
        @if($errors->any())
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
          </ul>
        </div>
        @endif

        <div class="form-group">
          <label>Nom complet *</label>
          <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Prénom NOM">
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>
        <div class="form-group">
          <label>Téléphone *</label>
          <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="+242...">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Mot de passe *</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="form-group">
            <label>Confirmer le mot de passe *</label>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <i class="fas fa-map-marker-alt" style="color:var(--tholad-blue);"></i>
        <h3>Adresse</h3>
      </div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Adresse</label>
          <input type="text" name="address" class="form-control" value="{{ old('address') }}">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Ville</label>
            <input type="text" name="city" class="form-control" value="{{ old('city','Pointe-Noire') }}">
          </div>
          <div class="form-group">
            <label>Pays</label>
            <input type="text" name="country" class="form-control" value="{{ old('country','Congo Brazzaville') }}">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Colonne droite : Infos professionnelles -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-briefcase" style="color:var(--tholad-blue);"></i>
        <h3>Informations professionnelles</h3>
      </div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Nom de la société (si applicable)</label>
          <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="Laisser vide si particulier">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Forme juridique</label>
            <select name="legal_form" class="form-control">
              <option value="">Choisir...</option>
              <option value="Particulier" {{ old('legal_form')=='Particulier'?'selected':'' }}>Particulier</option>
              <option value="SARL" {{ old('legal_form')=='SARL'?'selected':'' }}>SARL</option>
              <option value="SA" {{ old('legal_form')=='SA'?'selected':'' }}>SA</option>
              <option value="GIE" {{ old('legal_form')=='GIE'?'selected':'' }}>GIE</option>
              <option value="Association" {{ old('legal_form')=='Association'?'selected':'' }}>Association</option>
            </select>
          </div>
          <div class="form-group">
            <label>N° RCCM / Siret</label>
            <input type="text" name="siret" class="form-control" value="{{ old('siret') }}">
          </div>
        </div>
        <div class="form-group">
          <label>Commission ImmoStay (%)</label>
          <input type="number" name="commission_rate" class="form-control" value="{{ old('commission_rate', 10) }}" min="0" max="50" step="0.5">
          <small style="color:var(--txt3);font-size:12px;">% prélevé sur chaque réservation</small>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <i class="fas fa-mobile-alt" style="color:var(--tholad-blue);"></i>
        <h3>Coordonnées bancaires / Mobile Money</h3>
      </div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Numéro Mobile Money</label>
          <input type="text" name="mobile_money_number" class="form-control" value="{{ old('mobile_money_number') }}" placeholder="+242...">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Banque</label>
            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="LCB, BGFI...">
          </div>
          <div class="form-group">
            <label>Numéro de compte</label>
            <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account') }}">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div style="display:flex;gap:12px;margin-top:20px;">
  <button type="submit" class="btn btn-primary">
    <i class="fas fa-save"></i> Enregistrer le propriétaire
  </button>
  <a href="{{ route('admin.owners.index') }}" class="btn btn-outline">Annuler</a>
</div>
</form>
@endsection
