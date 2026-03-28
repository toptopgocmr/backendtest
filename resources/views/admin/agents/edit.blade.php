@extends('admin.layouts.app')
@section('title', 'Modifier agent — ' . $agent->name)

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.agents.show', $agent->id) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);">
    Modifier — {{ $agent->name }}
  </h2>
</div>

<form method="POST" action="{{ route('admin.agents.update', $agent->id) }}">
@csrf @method('PUT')

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
          <input type="text" name="name" class="form-control" value="{{ old('name', $agent->name) }}" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" value="{{ old('email', $agent->email) }}" required>
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="text" name="phone" class="form-control" value="{{ old('phone', $agent->phone) }}" placeholder="+242...">
        </div>
        <div class="form-group">
          <label>Matricule</label>
          <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $agent->employee_id) }}">
        </div>
        <div class="form-group">
          <label>Statut</label>
          <select name="status" class="form-control">
            @foreach(['actif','inactif','suspendu','congé'] as $s)
              <option value="{{ $s }}" {{ old('status', $agent->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:16px 0;">
        <p style="font-size:12px;color:var(--txt3);margin-bottom:12px;">Laisser vide pour ne pas changer le mot de passe</p>
        <div class="grid-2">
          <div class="form-group">
            <label>Nouveau mot de passe</label>
            <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
          </div>
          <div class="form-group">
            <label>Confirmer</label>
            <input type="password" name="password_confirmation" class="form-control">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="fas fa-phone" style="color:var(--tholad-blue);"></i><h3>Contact d'urgence</h3></div>
      <div style="padding:22px;">
        <div class="form-group">
          <label>Nom</label>
          <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $agent->emergency_contact_name) }}">
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $agent->emergency_contact_phone) }}">
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
          <select name="role" class="form-control" required>
            @foreach(['agent_commercial'=>'Agent Commercial','gestionnaire'=>'Gestionnaire','comptable'=>'Comptable','technicien'=>'Technicien','superviseur'=>'Superviseur','directeur'=>'Directeur'] as $val => $lbl)
              <option value="{{ $val }}" {{ old('role', $agent->role) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label>Département</label>
          <input type="text" name="department" class="form-control" value="{{ old('department', $agent->department) }}" placeholder="Commercial, Technique...">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Date d'embauche</label>
            <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', $agent->hire_date?->format('Y-m-d')) }}">
          </div>
          <div class="form-group">
            <label>Salaire (XAF)</label>
            <input type="number" name="salary" class="form-control" value="{{ old('salary', $agent->salary) }}" placeholder="0">
          </div>
        </div>
        <div class="form-group">
          <label>Notes internes</label>
          <textarea name="notes" class="form-control" rows="3">{{ old('notes', $agent->notes) }}</textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="fas fa-shield-alt" style="color:var(--tholad-blue);"></i><h3>Permissions</h3></div>
      <div style="padding:22px;">
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
          <input type="checkbox" name="{{ $key }}" value="1"
                 {{ old($key, $agent->$key) ? 'checked' : '' }}
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
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
  <a href="{{ route('admin.agents.show', $agent->id) }}" class="btn btn-outline">Annuler</a>
</div>
</form>
@endsection
