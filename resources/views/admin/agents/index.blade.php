@extends('admin.layouts.app')
@section('title', 'Agents TholadImmo')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--navy);">Agents TholadImmo</h2>
    <p style="color:var(--txt3);font-size:13px;margin-top:4px;">{{ $stats['total'] }} agent(s) enregistré(s)</p>
  </div>
  <a href="{{ route('admin.agents.create') }}" class="btn btn-primary">
    <i class="fas fa-user-plus"></i> Nouvel agent
  </a>
</div>

<!-- Stats -->
<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:#EFF6FF;color:#3B82F6;"><i class="fas fa-id-badge"></i></div>
    <div class="stat-value">{{ $stats['total'] }}</div>
    <div class="stat-label">Total agents</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ECFDF5;color:#10B981;"><i class="fas fa-check-circle"></i></div>
    <div class="stat-value">{{ $stats['actif'] }}</div>
    <div class="stat-label">Actifs</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FEF2F2;color:#EF4444;"><i class="fas fa-user-slash"></i></div>
    <div class="stat-value">{{ $stats['inactif'] }}</div>
    <div class="stat-label">Inactifs / Suspendus</div>
  </div>
</div>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px;">
  <div style="padding:16px 22px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
      <input type="text" name="search" class="form-control" placeholder="🔍 Nom, email, téléphone..."
             value="{{ request('search') }}" style="max-width:260px;">
      <select name="role" class="form-control" style="max-width:200px;">
        <option value="">Tous les rôles</option>
        <option value="agent_commercial" {{ request('role')=='agent_commercial'?'selected':'' }}>Agent Commercial</option>
        <option value="gestionnaire" {{ request('role')=='gestionnaire'?'selected':'' }}>Gestionnaire</option>
        <option value="comptable" {{ request('role')=='comptable'?'selected':'' }}>Comptable</option>
        <option value="technicien" {{ request('role')=='technicien'?'selected':'' }}>Technicien</option>
        <option value="superviseur" {{ request('role')=='superviseur'?'selected':'' }}>Superviseur</option>
        <option value="directeur" {{ request('role')=='directeur'?'selected':'' }}>Directeur</option>
      </select>
      <select name="status" class="form-control" style="max-width:160px;">
        <option value="">Tous statuts</option>
        <option value="actif" {{ request('status')=='actif'?'selected':'' }}>Actif</option>
        <option value="inactif" {{ request('status')=='inactif'?'selected':'' }}>Inactif</option>
        <option value="suspendu" {{ request('status')=='suspendu'?'selected':'' }}>Suspendu</option>
        <option value="congé" {{ request('status')=='congé'?'selected':'' }}>En congé</option>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
      <a href="{{ route('admin.agents.index') }}" class="btn btn-outline">Réinitialiser</a>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="card-header">
    <i class="fas fa-id-badge" style="color:var(--tholad-blue);"></i>
    <h3>Liste des agents</h3>
  </div>
  <table>
    <thead>
      <tr>
        <th>Agent</th>
        <th>Rôle</th>
        <th>Département</th>
        <th>Téléphone</th>
        <th>Permissions</th>
        <th>Embauché le</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($agents as $agent)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <img src="{{ $agent->avatar_url }}" alt="" style="width:36px;height:36px;border-radius:10px;object-fit:cover;">
            <div>
              <div style="font-weight:600;color:var(--navy);">{{ $agent->name }}</div>
              <div style="font-size:12px;color:var(--txt3);">{{ $agent->email }}</div>
            </div>
          </div>
        </td>
        <td>
          @php
          $roleColors = [
            'directeur'=>'#7C3AED','superviseur'=>'#2563EB','gestionnaire'=>'#0891B2',
            'comptable'=>'#059669','agent_commercial'=>'#EA580C','technicien'=>'#64748B'
          ];
          $rc = $roleColors[$agent->role] ?? '#64748B';
          @endphp
          <span style="background:{{ $rc }}18;color:{{ $rc }};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
            {{ $agent->role_label }}
          </span>
        </td>
        <td style="color:var(--txt2);">{{ $agent->department ?? '—' }}</td>
        <td style="color:var(--txt2);">{{ $agent->phone ?? '—' }}</td>
        <td>
          <div style="display:flex;gap:4px;flex-wrap:wrap;">
            @if($agent->can_manage_properties)
              <span title="Propriétés" style="background:#EFF6FF;color:#3B82F6;padding:2px 6px;border-radius:6px;font-size:10px;"><i class="fas fa-building"></i></span>
            @endif
            @if($agent->can_manage_bookings)
              <span title="Réservations" style="background:#ECFDF5;color:#10B981;padding:2px 6px;border-radius:6px;font-size:10px;"><i class="fas fa-calendar"></i></span>
            @endif
            @if($agent->can_manage_stock)
              <span title="Stock" style="background:#FFF7ED;color:#EA580C;padding:2px 6px;border-radius:6px;font-size:10px;"><i class="fas fa-boxes"></i></span>
            @endif
            @if($agent->can_manage_payments)
              <span title="Paiements" style="background:#F3E8FF;color:#7C3AED;padding:2px 6px;border-radius:6px;font-size:10px;"><i class="fas fa-credit-card"></i></span>
            @endif
          </div>
        </td>
        <td style="font-size:12px;color:var(--txt3);">{{ $agent->hire_date ? $agent->hire_date->format('d/m/Y') : '—' }}</td>
        <td>
          @if($agent->status === 'actif')
            <span class="badge-status actif">Actif</span>
          @elseif($agent->status === 'congé')
            <span class="badge-status en_attente">En congé</span>
          @elseif($agent->status === 'suspendu')
            <span class="badge-status annulé">Suspendu</span>
          @else
            <span class="badge-status terminé">Inactif</span>
          @endif
        </td>
        <td>
          <div style="display:flex;gap:6px;">
            <a href="{{ route('admin.agents.show', $agent->id) }}" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
            <a href="{{ route('admin.agents.edit', $agent->id) }}" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
            <form method="POST" action="{{ route('admin.agents.toggle', $agent->id) }}" style="display:inline;">
              @csrf @method('PUT')
              <button class="btn btn-sm {{ $agent->status==='actif' ? 'btn-danger' : 'btn-success' }}">
                {{ $agent->status==='actif' ? 'Désactiver' : 'Activer' }}
              </button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--txt3);">
        <i class="fas fa-id-badge" style="font-size:28px;margin-bottom:10px;display:block;"></i>
        Aucun agent trouvé
      </td></tr>
      @endforelse
    </tbody>
  </table>
  @if($agents->hasPages())
  <div style="padding:16px 22px;border-top:1px solid var(--border);">
    {{ $agents->withQueryString()->links() }}
  </div>
  @endif
</div>
@endsection
