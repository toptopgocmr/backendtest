@extends('admin.layouts.app')
@section('title', 'Propriétaire — ' . $user->name)

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.owners.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--navy);">
    Fiche propriétaire
  </h2>
</div>

<div class="grid-2" style="align-items:start;">

  <!-- Colonne gauche -->
  <div>
    <!-- Identité -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-user" style="color:var(--tholad-blue);"></i>
        <h3>Informations personnelles</h3>
        <a href="{{ route('admin.owners.edit', $user->id) }}" class="btn btn-sm btn-outline" style="margin-left:auto;">
          <i class="fas fa-edit"></i> Modifier
        </a>
      </div>
      <div style="padding:22px;">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px;">
          <div style="width:60px;height:60px;border-radius:14px;background:linear-gradient(135deg,var(--tholad-blue),var(--tholad-blue-dark));display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:22px;">
            {{ $user->initials }}
          </div>
          <div>
            <div style="font-size:20px;font-weight:700;color:var(--navy);font-family:'Cormorant Garamond',serif;">{{ $user->name }}</div>
            <div style="font-size:13px;color:var(--txt3);">{{ $user->email }}</div>
            @php $status = $user->ownerProfile->status ?? 'en_attente'; @endphp
            @if($status === 'vérifié')
              <span class="badge-status actif" style="margin-top:4px;display:inline-flex;">✓ Vérifié</span>
            @elseif($status === 'suspendu')
              <span class="badge-status annulé" style="margin-top:4px;display:inline-flex;">Suspendu</span>
            @else
              <span class="badge-status en_attente" style="margin-top:4px;display:inline-flex;">En attente</span>
            @endif
          </div>
        </div>

        @php
        $infos = [
          ['Téléphone', $user->phone ?? '—'],
          ['Pays', $user->country ?? '—'],
          ['Inscrit le', $user->created_at->format('d/m/Y')],
          ['Dernière connexion', $user->last_login_at ? $user->last_login_at->format('d/m/Y à H:i') : 'Jamais'],
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

    <!-- Profil professionnel -->
    @if($user->ownerProfile)
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-briefcase" style="color:var(--tholad-blue);"></i>
        <h3>Profil professionnel</h3>
      </div>
      <div style="padding:22px;">
        @php
        $p = $user->ownerProfile;
        $proInfos = [
          ['Société', $p->company_name ?? 'Particulier'],
          ['Forme juridique', $p->legal_form ?? '—'],
          ['N° RCCM / Siret', $p->siret ?? '—'],
          ['Contact référent', $p->contact_person ?? '—'],
          ['Téléphone pro', $p->contact_phone ?? '—'],
          ['Email pro', $p->contact_email ?? '—'],
          ['Adresse', $p->address ?? '—'],
          ['Ville', $p->city ?? '—'],
          ['Pays', $p->country ?? '—'],
          ['Commission', ($p->commission_rate ?? 10) . ' %'],
        ];
        @endphp
        @foreach($proInfos as [$label, $value])
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
          <span style="font-size:13px;color:var(--txt3);">{{ $label }}</span>
          <span style="font-size:13.5px;font-weight:600;color:var(--navy);">{{ $value }}</span>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Paiement -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-mobile-alt" style="color:var(--tholad-blue);"></i>
        <h3>Coordonnées bancaires</h3>
      </div>
      <div style="padding:22px;">
        @php
        $bankInfos = [
          ['Mobile Money', $p->mobile_money_number ?? '—'],
          ['Banque', $p->bank_name ?? '—'],
          ['N° Compte', $p->bank_account ?? '—'],
          ['Mode préféré', $p->preferred_payment ?? '—'],
        ];
        @endphp
        @foreach($bankInfos as [$label, $value])
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
          <span style="font-size:13px;color:var(--txt3);">{{ $label }}</span>
          <span style="font-size:13.5px;font-weight:600;color:var(--navy);">{{ $value }}</span>
        </div>
        @endforeach
      </div>
    </div>
    @endif
  </div>

  <!-- Colonne droite -->
  <div>
    <!-- Actions -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-tools" style="color:var(--tholad-blue);"></i>
        <h3>Actions</h3>
      </div>
      <div style="padding:22px;display:flex;flex-direction:column;gap:10px;">
        @if(($user->ownerProfile->status ?? '') !== 'vérifié')
        <form method="POST" action="{{ route('admin.owners.verify', $user->id) }}">
          @csrf @method('PUT')
          <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;">
            <i class="fas fa-check-circle"></i> Vérifier ce propriétaire
          </button>
        </form>
        @endif

        <form method="POST" action="{{ route('admin.owners.toggle', $user->id) }}">
          @csrf @method('PUT')
          <button type="submit" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }}" style="width:100%;justify-content:center;">
            @if($user->is_active)
              <i class="fas fa-ban"></i> Suspendre le compte
            @else
              <i class="fas fa-check"></i> Réactiver le compte
            @endif
          </button>
        </form>

        <a href="{{ route('admin.owners.edit', $user->id) }}" class="btn btn-outline" style="width:100%;justify-content:center;">
          <i class="fas fa-edit"></i> Modifier les informations
        </a>
      </div>
    </div>

    <!-- Notes admin -->
    @if($user->ownerProfile && $user->ownerProfile->notes)
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <i class="fas fa-sticky-note" style="color:var(--tholad-blue);"></i>
        <h3>Notes internes</h3>
      </div>
      <div style="padding:22px;">
        <p style="font-size:13.5px;color:var(--txt2);line-height:1.6;">{{ $user->ownerProfile->notes }}</p>
      </div>
    </div>
    @endif

    <!-- Propriétés -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-building" style="color:var(--tholad-blue);"></i>
        <h3>Propriétés ({{ $user->properties->count() }})</h3>
      </div>
      @if($user->properties->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--txt3);">
          <i class="fas fa-building" style="font-size:24px;margin-bottom:8px;display:block;"></i>
          Aucune propriété enregistrée
        </div>
      @else
      <table>
        <thead>
          <tr>
            <th>Propriété</th>
            <th>Type</th>
            <th>Ville</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          @foreach($user->properties as $prop)
          <tr>
            <td>
              <a href="{{ route('admin.properties.show', $prop->id) }}" style="font-weight:600;color:var(--tholad-blue);text-decoration:none;">
                {{ Str::limit($prop->title, 35) }}
              </a>
            </td>
            <td style="color:var(--txt2);font-size:12px;">{{ ucfirst($prop->type) }}</td>
            <td style="color:var(--txt2);font-size:12px;">{{ $prop->city }}</td>
            <td>
              <span class="badge-status {{ $prop->status }}">{{ ucfirst($prop->status) }}</span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    </div>
  </div>
</div>
@endsection
