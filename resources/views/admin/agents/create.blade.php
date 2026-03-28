@extends('admin.layouts.app')
@section('title', 'Nouvel agent')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.agents.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">Créer un compte agent TholadImmo</h2>
</div>

<form method="POST" action="{{ route('admin.agents.store') }}">
@csrf
<div class="grid-2" style="align-items:start;">

  <!-- Gauche -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><i class="fas fa-user" style="color:var(--tholad-blue);"></i><h3>Identité</h3></div>
      <div style="padding:22px;">
        @if($errors->any())
          <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
            <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          </div>
        @endif
        <div class="form-group">
          <label>Nom complet *</label>
          <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+242...">
        </div>
        <div class="form-group">
          <label>Matricule (optionnel)</label>
          <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id') }}" placeholder="Ex: THL-2026-001">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Mot de passe *</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="form-group">
            <label>Confirmer *</label>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="fas fa-phone" style="color:var(--tholad-blue);"></i><h3>Contact d'urgence</h3></div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Nom</label>
          <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}">
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}">
        </div>
      </div>
    </div>
  </div>

  <!-- Droite -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><i class="fas fa-briefcase" style="color:var(--tholad-blue);"></i><h3>Poste & rôle</h3></div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Rôle *</label>
          <select name="role" class="form-control" required id="role-select">
            <option value="">Choisir un rôle...</option>
            <option value="agent_commercial" {{ old('role')=='agent_commercial'?'selected':'' }}>Agent Commercial</option>
            <option value="gestionnaire"     {{ old('role')=='gestionnaire'?'selected':'' }}>Gestionnaire</option>
            <option value="comptable"        {{ old('role')=='comptable'?'selected':'' }}>Comptable</option>
            <option value="technicien"       {{ old('role')=='technicien'?'selected':'' }}>Technicien</option>
            <option value="superviseur"      {{ old('role')=='superviseur'?'selected':'' }}>Superviseur</option>
            <option value="directeur"        {{ old('role')=='directeur'?'selected':'' }}>Directeur</option>
          </select>
        </div>
        <div class="form-group">
          <label>Département / Service</label>
          <input type="text" name="department" class="form-control" value="{{ old('department') }}" placeholder="Ex: Commercial, Technique...">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Date d'embauche</label>
            <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', date('Y-m-d')) }}">
          </div>
          <div class="form-group">
            <label>Salaire (XAF)</label>
            <input type="number" name="salary" class="form-control" value="{{ old('salary') }}" placeholder="0">
          </div>
        </div>
        <div class="form-group">
          <label>Notes internes</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Informations complémentaires...">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="fas fa-shield-alt" style="color:var(--tholad-blue);"></i><h3>Permissions</h3></div>
      <div style="padding:22px;">
        <p style="font-size:12px;color:var(--txt3);margin-bottom:14px;">Les permissions sont pré-configurées selon le rôle. Vous pouvez les ajuster :</p>
        @php
        $perms = [
          'can_manage_properties' => ['label'=>'Gérer les propriétés','icon'=>'building'],
          'can_manage_bookings'   => ['label'=>'Gérer les réservations','icon'=>'calendar-check'],
          'can_manage_stock'      => ['label'=>'Gérer les stocks','icon'=>'boxes'],
          'can_manage_payments'   => ['label'=>'Gérer les paiements','icon'=>'credit-card'],
          'can_view_reports'      => ['label'=>'Voir les rapports','icon'=>'chart-bar'],
        ];
        @endphp
        @foreach($perms as $key => $perm)
        <label style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);cursor:pointer;">
          <input type="checkbox" name="{{ $key }}" value="1" {{ old($key,1)?'checked':'' }}
                 style="width:16px;height:16px;accent-color:var(--tholad-blue);">
          <i class="fas fa-{{ $perm['icon'] }}" style="color:var(--tholad-blue);width:16px;"></i>
          <span style="font-size:13.5px;">{{ $perm['label'] }}</span>
        </label>
        @endforeach
      </div>
    </div>
  </div>
</div>

<div style="display:flex;gap:12px;margin-top:20px;">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer l'agent</button>
  <a href="{{ route('admin.agents.index') }}" class="btn btn-outline">Annuler</a>
</div>
</form>
@endsection
