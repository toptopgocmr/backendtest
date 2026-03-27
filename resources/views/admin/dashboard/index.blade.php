@extends('admin.layouts.app')
@section('title', 'Dashboard')

@section('content')
<!-- Stats -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#EFF6FF;color:#3B82F6"><i class="fas fa-building"></i></div>
    <div class="stat-value">{{ $stats['total_properties'] }}</div>
    <div class="stat-label">Propriétés actives</div>
    <div class="stat-change up"><i class="fas fa-arrow-up"></i> +{{ $stats['new_properties'] }} ce mois</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ECFDF5;color:#10B981"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-value">{{ $stats['total_bookings'] }}</div>
    <div class="stat-label">Réservations totales</div>
    <div class="stat-change up"><i class="fas fa-arrow-up"></i> {{ $stats['pending_bookings'] }} en attente</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FFF7ED;color:#EA580C"><i class="fas fa-coins"></i></div>
    <div class="stat-value">{{ number_format($stats['total_revenue'], 0, ',', ' ') }}</div>
    <div class="stat-label">Revenus XAF</div>
    <div class="stat-change up"><i class="fas fa-arrow-up"></i> +12% vs mois dernier</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--goldpal);color:var(--gold)"><i class="fas fa-users"></i></div>
    <div class="stat-value">{{ $stats['total_users'] }}</div>
    <div class="stat-label">Utilisateurs inscrits</div>
    <div class="stat-change up"><i class="fas fa-arrow-up"></i> +{{ $stats['new_users'] }} nouveaux</div>
  </div>
</div>

<div class="grid-2" style="margin-bottom:20px">
  <!-- Recent Bookings -->
  <div class="card">
    <div class="card-header">
      <h3>📅 Réservations récentes</h3>
      <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <table>
      <thead><tr><th>Référence</th><th>Client</th><th>Bien</th><th>Montant</th><th>Statut</th></tr></thead>
      <tbody>
      @foreach($recent_bookings as $booking)
      <tr>
        <td><strong style="color:var(--gold)">{{ $booking->reference }}</strong></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div class="avatar">{{ strtoupper(substr($booking->user->name,0,1)) }}</div>
            <span>{{ $booking->user->name }}</span>
          </div>
        </td>
        <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $booking->property->title ?? '—' }}</td>
        <td><strong>{{ number_format($booking->total_amount,0,',',' ') }} {{ $booking->currency }}</strong></td>
        <td><span class="badge-status {{ $booking->status }}">{{ $booking->status }}</span></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <!-- Recent Payments -->
  <div class="card">
    <div class="card-header">
      <h3>💳 Paiements récents</h3>
      <a href="{{ route('admin.payments.index') }}" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <table>
      <thead><tr><th>Réf.</th><th>Méthode</th><th>Montant</th><th>Statut</th></tr></thead>
      <tbody>
      @foreach($recent_payments as $payment)
      <tr>
        <td><strong style="color:var(--navy2)">{{ $payment->reference }}</strong></td>
        <td>
          @php $icons=['mtn_momo'=>'📱','airtel_money'=>'📲','orange_money'=>'🟠','virement'=>'🏦','carte'=>'💳']; @endphp
          {{ $icons[$payment->method] ?? '💳' }} {{ $payment->method_label }}
        </td>
        <td><strong>{{ number_format($payment->amount,0,',',' ') }}</strong></td>
        <td><span class="badge-status {{ $payment->status }}">{{ $payment->status }}</span></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- Top Properties + Users -->
<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <h3>🏆 Meilleures propriétés</h3>
    </div>
    <table>
      <thead><tr><th>Bien</th><th>Ville</th><th>Note</th><th>Réservations</th></tr></thead>
      <tbody>
      @foreach($top_properties as $p)
      <tr>
        <td>
          <strong>{{ Str::limit($p->title,30) }}</strong>
          <div style="font-size:11px;color:var(--txt3)">{{ $p->type }}</div>
        </td>
        <td>{{ $p->city }}</td>
        <td>⭐ {{ $p->rating }}</td>
        <td><strong style="color:var(--navy2)">{{ $p->bookings_count }}</strong></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>👥 Nouveaux utilisateurs</h3>
      <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <table>
      <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Inscrit le</th><th>Statut</th></tr></thead>
      <tbody>
      @foreach($new_users as $user)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div class="avatar">{{ strtoupper(substr($user->name,0,1)) }}</div>
            <div>
              <div style="font-weight:600">{{ $user->name }}</div>
              <div style="font-size:11px;color:var(--txt3)">{{ $user->phone }}</div>
            </div>
          </div>
        </td>
        <td>
          @php $roleColors=['client'=>'#3B82F6','owner'=>'#B8860B','admin'=>'#EF4444']; @endphp
          <span style="color:{{ $roleColors[$user->role] ?? '#666' }};font-weight:600;font-size:12px">{{ $user->role }}</span>
        </td>
        <td style="font-size:12px;color:var(--txt3)">{{ $user->created_at->diffForHumans() }}</td>
        <td><span class="badge-status {{ $user->is_active ? 'actif' : 'annulé' }}">{{ $user->is_active ? 'actif' : 'inactif' }}</span></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
