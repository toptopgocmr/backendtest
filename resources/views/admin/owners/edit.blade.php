@extends('admin.layouts.app')
@section('title', 'Modifier propriétaire — ' . $user->name)

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.owners.show', $user->id) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">
    Modifier — {{ $user->name }}
  </h2>
</div>

<form method="POST" action="{{ route('admin.owners.update', $user->id) }}">
@csrf @method('PUT')

<div class="grid-2" style="align-items:start;">

  <!-- Colonne gauche -->
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
          <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-map-marker-alt" style="color:var(--tholad-blue);"></i>
        <h3>Adresse</h3>
      </div>
      <div style="padding:22px;">
        @php $p = $user->ownerProfile; @endphp
        <div class="form-group">
          <label>Adresse</label>
          <input type="text" name="address" class="form-control" value="{{ old('address', $p->address ?? '') }}">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Ville</label>
            <input type="text" name="city" class="form-control" value="{{ old('city', $p->city ?? 'Pointe-Noire') }}">
          </div>
          <div class="form-group">
            <label>Pays</label>
            <input type="text" name="country" class="form-control" value="{{ old('country', $p->country ?? 'Congo Brazzaville') }}">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <i class="fas fa-mobile-alt" style="color:var(--tholad-blue);"></i>
        <h3>Coordonnées bancaires</h3>
      </div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Numéro Mobile Money</label>
          <input type="text" name="mobile_money_number" class="form-control" value="{{ old('mobile_money_number', $p->mobile_money_number ?? '') }}" placeholder="+242...">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Banque</label>
            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $p->bank_name ?? '') }}" placeholder="LCB, BGFI...">
          </div>
          <div class="form-group">
            <label>N° de compte</label>
            <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $p->bank_account ?? '') }}">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Colonne droite -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-briefcase" style="color:var(--tholad-blue);"></i>
        <h3>Informations professionnelles</h3>
      </div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Nom de la société</label>
          <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $p->company_name ?? '') }}" placeholder="Laisser vide si particulier">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Forme juridique</label>
            <select name="legal_form" class="form-control">
              <option value="">Choisir...</option>
              @foreach(['Particulier','SARL','SA','GIE','Association'] as $lf)
                <option value="{{ $lf }}" {{ old('legal_form', $p->legal_form ?? '') === $lf ? 'selected' : '' }}>{{ $lf }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>N° RCCM / Siret</label>
            <input type="text" name="siret" class="form-control" value="{{ old('siret', $p->siret ?? '') }}">
          </div>
        </div>
        <div class="form-group">
          <label>Personne de contact</label>
          <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $p->contact_person ?? '') }}">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Téléphone pro</label>
            <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $p->contact_phone ?? '') }}">
          </div>
          <div class="form-group">
            <label>Email pro</label>
            <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $p->contact_email ?? '') }}">
          </div>
        </div>
        <div class="form-group">
          <label>Commission ImmoStay (%)</label>
          <input type="number" name="commission_rate" class="form-control" value="{{ old('commission_rate', $p->commission_rate ?? 10) }}" min="0" max="50" step="0.5">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <i class="fas fa-sticky-note" style="color:var(--tholad-blue);"></i>
        <h3>Notes internes</h3>
      </div>
      <div style="padding:22px;">
        <div class="form-group" style="margin-bottom:0;">
          <textarea name="notes" class="form-control" rows="5" placeholder="Observations, informations complémentaires...">{{ old('notes', $p->notes ?? '') }}</textarea>
        </div>
      </div>
    </div>
  </div>
</div>

<div style="display:flex;gap:12px;margin-top:20px;">
  <button type="submit" class="btn btn-primary">
    <i class="fas fa-save"></i> Enregistrer les modifications
  </button>
  <a href="{{ route('admin.owners.show', $user->id) }}" class="btn btn-outline">Annuler</a>
</div>
</form>
@endsection
