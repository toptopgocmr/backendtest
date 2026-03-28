@extends('admin.layouts.app')
@section('title', 'Propriétaires')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--navy);">Gestion des Propriétaires</h2>
    <p style="color:var(--txt3);font-size:13px;margin-top:4px;">{{ $stats['total'] }} propriétaire(s) enregistré(s)</p>
  </div>
  <a href="{{ route('admin.owners.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Nouveau propriétaire
  </a>
</div>

<!-- Stats -->
<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:#EFF6FF;color:#3B82F6;"><i class="fas fa-users"></i></div>
    <div class="stat-value">{{ $stats['total'] }}</div>
    <div class="stat-label">Total propriétaires</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ECFDF5;color:#10B981;"><i class="fas fa-check-circle"></i></div>
    <div class="stat-value">{{ $stats['verified'] }}</div>
    <div class="stat-label">Vérifiés</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FFF7ED;color:#EA580C;"><i class="fas fa-clock"></i></div>
    <div class="stat-value">{{ $stats['pending'] }}</div>
    <div class="stat-label">En attente</div>
  </div>
</div>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px;overflow:visible;">
  <div style="padding:16px 22px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
      <input type="text" name="search" class="form-control" placeholder="🔍 Nom, téléphone, email..."
             value="{{ request('search') }}" style="max-width:280px;">
      <select name="status" class="form-control" style="max-width:180px;">
        <option value="">Tous les statuts</option>
        <option value="en_attente" {{ request('status')=='en_attente'?'selected':'' }}>En attente</option>
        <option value="vérifié" {{ request('status')=='vérifié'?'selected':'' }}>Vérifiés</option>
        <option value="suspendu" {{ request('status')=='suspendu'?'selected':'' }}>Suspendus</option>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
      <a href="{{ route('admin.owners.index') }}" class="btn btn-outline">Réinitialiser</a>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="card-header">
    <i class="fas fa-home" style="color:var(--tholad-blue);"></i>
    <h3>Liste des propriétaires</h3>
  </div>
  <table>
    <thead>
      <tr>
        <th>Propriétaire</th>
        <th>Société / Type</th>
        <th>Contact</th>
        <th>Biens</th>
        <th>Commission</th>
        <th>Statut</th>
        <th>Inscrit le</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($owners as $user)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar">{{ $user->initials }}</div>
            <div>
              <div style="font-weight:600;color:var(--navy);">{{ $user->name }}</div>
              <div style="font-size:12px;color:var(--txt3);">{{ $user->email }}</div>
            </div>
          </div>
        </td>
        <td>
          @if($user->ownerProfile && $user->ownerProfile->company_name)
            <div style="font-weight:600;">{{ $user->ownerProfile->company_name }}</div>
            <div style="font-size:12px;color:var(--txt3);">{{ $user->ownerProfile->legal_form ?? 'Particulier' }}</div>
          @else
            <span style="color:var(--txt3);">Particulier</span>
          @endif
        </td>
        <td>
          <div>{{ $user->phone }}</div>
          @if($user->ownerProfile && $user->ownerProfile->city)
            <div style="font-size:12px;color:var(--txt3);">{{ $user->ownerProfile->city }}</div>
          @endif
        </td>
        <td>
          <span style="font-weight:700;color:var(--tholad-blue);">{{ $user->properties_count ?? $user->properties->count() }}</span>
        </td>
        <td>
          {{ $user->ownerProfile ? $user->ownerProfile->commission_rate : 10 }}%
        </td>
        <td>
          @php $status = $user->ownerProfile->status ?? 'en_attente'; @endphp
          @if($status === 'vérifié')
            <span class="badge-status actif">✓ Vérifié</span>
          @elseif($status === 'suspendu')
            <span class="badge-status annulé">Suspendu</span>
          @else
            <span class="badge-status en_attente">En attente</span>
          @endif
        </td>
        <td style="font-size:12px;color:var(--txt3);">{{ $user->created_at->format('d/m/Y') }}</td>
        <td>
          <div style="display:flex;gap:6px;">
            <a href="{{ route('admin.owners.show', $user->id) }}" class="btn btn-sm btn-outline">
              <i class="fas fa-eye"></i>
            </a>
            @if(($user->ownerProfile->status ?? '') !== 'vérifié')
            <form method="POST" action="{{ route('admin.owners.verify', $user->id) }}" style="display:inline;">
              @csrf @method('PUT')
              <button type="submit" class="btn btn-sm btn-success" title="Vérifier">
                <i class="fas fa-check"></i>
              </button>
            </form>
            @endif
            <form method="POST" action="{{ route('admin.owners.toggle', $user->id) }}" style="display:inline;">
              @csrf @method('PUT')
              <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}">
                {{ $user->is_active ? 'Suspendre' : 'Activer' }}
              </button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--txt3);">
        <i class="fas fa-home" style="font-size:28px;margin-bottom:10px;display:block;"></i>
        Aucun propriétaire trouvé
      </td></tr>
      @endforelse
    </tbody>
  </table>
  @if($owners->hasPages())
  <div style="padding:16px 22px;border-top:1px solid var(--border);">
    {{ $owners->withQueryString()->links() }}
  </div>
  @endif
</div>
@endsection
