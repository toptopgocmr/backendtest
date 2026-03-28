@extends('admin.layouts.app')
@section('title', 'Agent — ' . $agent->name)

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.agents.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--navy);">Fiche agent</h2>
</div>

<div class="grid-2" style="align-items:start;">

  <!-- Colonne gauche -->
  <div>
    <!-- Identité -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-id-badge" style="color:var(--tholad-blue);"></i>
        <h3>Identité</h3>
        <a href="{{ route('admin.agents.edit', $agent->id) }}" class="btn btn-sm btn-outline" style="margin-left:auto;">
          <i class="fas fa-edit"></i> Modifier
        </a>
      </div>
      <div style="padding:22px;">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px;">
          <img src="{{ $agent->avatar_url }}" alt="{{ $agent->name }}" style="width:64px;height:64px;border-radius:14px;object-fit:cover;">
          <div>
            <div style="font-size:20px;font-weight:700;color:var(--navy);font-family:'Cormorant Garamond',serif;">{{ $agent->name }}</div>
            <div style="font-size:13px;color:var(--txt3);">{{ $agent->email }}</div>
            @php
            $roleColors = [
              'directeur'=>'#7C3AED','superviseur'=>'#2563EB','gestionnaire'=>'#0891B2',
              'comptable'=>'#059669','agent_commercial'=>'#EA580C','technicien'=>'#64748B'
            ];
            $rc = $roleColors[$agent->role] ?? '#64748B';
            @endphp
            <span style="background:{{ $rc }}18;color:{{ $rc }};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;margin-top:4px;display:inline-block;">
              {{ $agent->role_label }}
            </span>
          </div>
        </div>

        @php
        $infos = [
          ['Téléphone', $agent->phone ?? '—'],
          ['Département', $agent->department ?? '—'],
          ['Matricule', $agent->employee_id ?? '—'],
          ['Date d\'embauche', $agent->hire_date ? $agent->hire_date->format('d/m/Y') : '—'],
          ['Salaire', $agent->salary ? number_format($agent->salary, 0, ',', ' ') . ' ' . $agent->salary_currency : '—'],
          ['Dernière connexion', $agent->last_login_at ? $agent->last_login_at->format('d/m/Y à H:i') : 'Jamais'],
        ];
        @endphp
        @foreach($infos as [$label, $value])
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
          <span style="font-size:13px;color:var(--txt3);">{{ $label }}</span>
          <span style="font-size:13.5px;font-weight:600;color:var(--navy);">{{ $value }}</span>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Contact d'urgence -->
    @if($agent->emergency_contact_name)
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-phone-alt" style="color:var(--tholad-blue);"></i>
        <h3>Contact d'urgence</h3>
      </div>
      <div style="padding:22px;">
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
          <span style="font-size:13px;color:var(--txt3);">Nom</span>
          <span style="font-size:13.5px;font-weight:600;color:var(--navy);">{{ $agent->emergency_contact_name }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;">
          <span style="font-size:13px;color:var(--txt3);">Téléphone</span>
          <span style="font-size:13.5px;font-weight:600;color:var(--navy);">{{ $agent->emergency_contact_phone ?? '—' }}</span>
        </div>
      </div>
    </div>
    @endif

    @if($agent->notes)
    <div class="card">
      <div class="card-header">
        <i class="fas fa-sticky-note" style="color:var(--tholad-blue);"></i>
        <h3>Notes internes</h3>
      </div>
      <div style="padding:22px;">
        <p style="font-size:13.5px;color:var(--txt2);line-height:1.6;">{{ $agent->notes }}</p>
      </div>
    </div>
    @endif
  </div>

  <!-- Colonne droite -->
  <div>
    <!-- Statut & Actions -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-tools" style="color:var(--tholad-blue);"></i>
        <h3>Statut & Actions</h3>
      </div>
      <div style="padding:22px;">
        <div style="text-align:center;margin-bottom:18px;">
          @if($agent->status === 'actif')
            <span class="badge-status actif" style="font-size:14px;padding:8px 20px;">● Actif</span>
          @elseif($agent->status === 'congé')
            <span class="badge-status en_attente" style="font-size:14px;padding:8px 20px;">En congé</span>
          @elseif($agent->status === 'suspendu')
            <span class="badge-status annulé" style="font-size:14px;padding:8px 20px;">Suspendu</span>
          @else
            <span class="badge-status terminé" style="font-size:14px;padding:8px 20px;">Inactif</span>
          @endif
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <form method="POST" action="{{ route('admin.agents.toggle', $agent->id) }}">
            @csrf @method('PUT')
            <button type="submit" class="btn {{ $agent->status === 'actif' ? 'btn-danger' : 'btn-success' }}" style="width:100%;justify-content:center;">
              @if($agent->status === 'actif')
                <i class="fas fa-pause-circle"></i> Désactiver l'agent
              @else
                <i class="fas fa-play-circle"></i> Activer l'agent
              @endif
            </button>
          </form>
          <a href="{{ route('admin.agents.edit', $agent->id) }}" class="btn btn-outline" style="width:100%;justify-content:center;">
            <i class="fas fa-edit"></i> Modifier
          </a>
          <form method="POST" action="{{ route('admin.agents.destroy', $agent->id) }}"
                onsubmit="return confirm('Supprimer cet agent ? Cette action est irréversible.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">
              <i class="fas fa-trash"></i> Supprimer l'agent
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Permissions -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-shield-alt" style="color:var(--tholad-blue);"></i>
        <h3>Permissions</h3>
      </div>
      <div style="padding:22px;">
        @php
        $perms = [
          ['can_manage_properties', 'Gérer les propriétés', 'building', '#3B82F6'],
          ['can_manage_bookings',   'Gérer les réservations', 'calendar-check', '#10B981'],
          ['can_manage_stock',      'Gérer les stocks', 'boxes', '#EA580C'],
          ['can_manage_payments',   'Gérer les paiements', 'credit-card', '#7C3AED'],
          ['can_view_reports',      'Voir les rapports', 'chart-bar', '#0891B2'],
        ];
        @endphp
        @foreach($perms as [$key, $label, $icon, $color])
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
          <div style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-{{ $icon }}" style="color:{{ $color }};width:18px;"></i>
            <span style="font-size:13.5px;">{{ $label }}</span>
          </div>
          @if($agent->$key)
            <span style="color:#10B981;font-weight:700;font-size:12px;"><i class="fas fa-check-circle"></i> Oui</span>
          @else
            <span style="color:#EF4444;font-weight:700;font-size:12px;"><i class="fas fa-times-circle"></i> Non</span>
          @endif
        </div>
        @endforeach
      </div>
    </div>

    <!-- Derniers mouvements de stock -->
    @if($agent->stockMovements->count() > 0)
    <div class="card">
      <div class="card-header">
        <i class="fas fa-history" style="color:var(--tholad-blue);"></i>
        <h3>Derniers mouvements stock</h3>
      </div>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Article</th>
            <th>Type</th>
            <th>Qté</th>
          </tr>
        </thead>
        <tbody>
          @foreach($agent->stockMovements->take(8) as $mv)
          <tr>
            <td style="font-size:12px;color:var(--txt3);">{{ $mv->created_at->format('d/m H:i') }}</td>
            <td style="font-size:13px;">{{ $mv->stockItem->name ?? '—' }}</td>
            <td>
              @if($mv->type === 'entrée')
                <span style="color:#10B981;font-size:12px;font-weight:700;">➕ Entrée</span>
              @else
                <span style="color:#EF4444;font-size:12px;font-weight:700;">➖ Sortie</span>
              @endif
            </td>
            <td style="font-weight:700;font-size:13px;color:{{ $mv->type === 'entrée' ? '#10B981' : '#EF4444' }};">
              {{ $mv->type === 'entrée' ? '+' : '-' }}{{ number_format($mv->quantity, 1) }}
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
</div>
@endsection
