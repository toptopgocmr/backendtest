{{-- resources/views/admin/properties/show.blade.php --}}
@extends('admin.layouts.app')
@section('title', Str::limit($property->title, 40))
@section('content')
<div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
  <a href="{{ route('admin.properties.index') }}" class="btn btn-outline btn-sm">← Retour</a>
  <div style="flex:1">
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700">{{ $property->title }}</h2>
    <p style="color:var(--txt3);font-size:13px">📍 {{ $property->city }}, {{ $property->district }} • Ajoutée {{ $property->created_at->diffForHumans() }}</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    @if(!$property->is_approved)
    <form action="{{ route('admin.properties.approve', $property->id) }}" method="POST">
      @csrf @method('PUT')
      <button type="submit" class="btn btn-gold">✓ Approuver</button>
    </form>
    @else
    <span class="badge-status actif">✓ Approuvé</span>
    @endif
    <span class="badge-status {{ $property->status }}">{{ $property->status }}</span>
  </div>
</div>

<!-- Images -->
@if($property->images->count())
<div style="display:flex;gap:10px;margin-bottom:24px;overflow-x:auto">
  @foreach($property->images as $img)
  <img src="{{ $img->url }}" style="height:180px;width:auto;border-radius:12px;object-fit:cover;{{ $img->is_primary ? 'border:3px solid var(--gold)' : '' }}">
  @endforeach
</div>
@endif

<div class="grid-3" style="margin-bottom:20px">
  <!-- Details -->
  <div class="card" style="grid-column:span 2">
    <div class="card-header"><h3>📋 Informations</h3></div>
    <div style="padding:20px">
      @php
        $habitTypes  = ['appartement','villa','studio','maison','chambre'];
        $bureauTypes = ['bureau','salle_reunion'];
        $feteTypes   = ['salle_fete'];
        $terrainTypes= ['terrain','entrepot','commerce'];
        $type = $property->type;

        $periodeLabels = [
          'heure'   => 'heure',
          'nuit'    => 'nuit',
          'jour'    => 'jour',
          'semaine' => 'semaine',
          'mois'    => 'mois',
          'an'      => 'an',
          'total'   => 'prix total',
        ];
        $periodeLabel = $periodeLabels[$property->price_period] ?? $property->price_period;

        $typeLabels = [
          'appartement'  => 'Appartement',
          'villa'        => 'Villa',
          'studio'       => 'Studio',
          'maison'       => 'Maison',
          'chambre'      => 'Chambre',
          'bureau'       => 'Bureau',
          'salle_reunion'=> 'Salle de réunion',
          'salle_fete'   => 'Salle des fêtes',
          'terrain'      => 'Terrain',
          'entrepot'     => 'Entrepôt',
          'commerce'     => 'Commerce',
        ];
        $typeLabel = $typeLabels[$type] ?? ucfirst($type);
      @endphp

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

        {{-- Type & Prix : toujours affichés --}}
        <div><div style="font-size:11px;color:var(--txt3)">TYPE</div><div style="font-weight:600">{{ $typeLabel }}</div></div>
        <div>
          <div style="font-size:11px;color:var(--txt3)">PRIX</div>
          <div style="font-weight:700;color:var(--gold)">
            {{ number_format($property->price,0,',',' ') }} {{ $property->currency }} / {{ $periodeLabel }}
            @if($property->price_period === 'heure' && $property->duration_hours)
              <span style="font-size:12px;color:var(--txt3);font-weight:400"> (min. {{ $property->duration_hours }}h)</span>
            @endif
          </div>
        </div>

        {{-- Champs HABITATION --}}
        @if(in_array($type, $habitTypes))
          <div><div style="font-size:11px;color:var(--txt3)">CHAMBRES</div><div style="font-weight:600">{{ $property->bedrooms ?? '—' }}</div></div>
          <div><div style="font-size:11px;color:var(--txt3)">SALLES DE BAIN</div><div style="font-weight:600">{{ $property->bathrooms ?? '—' }}</div></div>
          <div><div style="font-size:11px;color:var(--txt3)">PERSONNES MAX</div><div style="font-weight:600">{{ $property->max_guests ?? '—' }} personnes</div></div>
          <div><div style="font-size:11px;color:var(--txt3)">SURFACE</div><div style="font-weight:600">{{ $property->area ? $property->area.' m²' : '—' }}</div></div>
          @if($property->floor)
          <div><div style="font-size:11px;color:var(--txt3)">ÉTAGE</div><div style="font-weight:600">{{ $property->floor === 'rdc' ? 'Rez-de-chaussée' : $property->floor.'ème étage' }}</div></div>
          @endif
          @if($property->view_type)
          <div><div style="font-size:11px;color:var(--txt3)">VUE</div><div style="font-weight:600">{{ ucfirst($property->view_type) }}</div></div>
          @endif
        @endif

        {{-- Champs BUREAU / SALLE DE RÉUNION --}}
        @if(in_array($type, $bureauTypes) || in_array($type, $feteTypes))
          <div><div style="font-size:11px;color:var(--txt3)">CAPACITÉ</div><div style="font-weight:600">{{ $property->capacity ?? '—' }} personnes</div></div>
          <div><div style="font-size:11px;color:var(--txt3)">SURFACE</div><div style="font-weight:600">{{ $property->area ? $property->area.' m²' : '—' }}</div></div>
          @if($property->floor)
          <div><div style="font-size:11px;color:var(--txt3)">ÉTAGE</div><div style="font-weight:600">{{ $property->floor === 'rdc' ? 'Rez-de-chaussée' : $property->floor.'ème étage' }}</div></div>
          @endif
          @if($property->workstations)
          <div><div style="font-size:11px;color:var(--txt3)">POSTES DE TRAVAIL</div><div style="font-weight:600">{{ $property->workstations }}</div></div>
          @endif
        @endif

        {{-- Champs TERRAIN / ENTREPÔT / COMMERCE --}}
        @if(in_array($type, $terrainTypes))
          <div><div style="font-size:11px;color:var(--txt3)">SUPERFICIE</div><div style="font-weight:600">{{ $property->area ? number_format($property->area,0,',',' ').' m²' : '—' }}</div></div>
          @if($property->terrain_type)
          <div><div style="font-size:11px;color:var(--txt3)">TYPE DE TERRAIN</div><div style="font-weight:600">{{ ucfirst($property->terrain_type) }}</div></div>
          @endif
          @if($property->land_title)
          <div><div style="font-size:11px;color:var(--txt3)">TITRE FONCIER</div><div style="font-weight:600">{{ $property->land_title }}</div></div>
          @endif
        @endif

        {{-- Note & Vues : toujours affichées --}}
        <div><div style="font-size:11px;color:var(--txt3)">NOTE MOYENNE</div><div style="font-weight:600">⭐ {{ $property->rating ?? 0 }} ({{ $property->reviews_count ?? 0 }} avis)</div></div>
        <div><div style="font-size:11px;color:var(--txt3)">VUES</div><div style="font-weight:600">👁 {{ $property->views_count ?? 0 }}</div></div>

      </div>
      <div style="border-top:1px solid var(--border);padding-top:14px">
        <div style="font-size:12px;color:var(--txt3);margin-bottom:6px">DESCRIPTION</div>
        <p style="color:var(--txt2);line-height:1.7;font-size:13.5px">{{ $property->description ?? 'Aucune description.' }}</p>
      </div>
      @if($property->amenities->count())
      <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:14px">
        <div style="font-size:12px;color:var(--txt3);margin-bottom:10px">ÉQUIPEMENTS</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
          @foreach($property->amenities as $a)
          <span style="padding:5px 12px;background:var(--bg);border:1px solid var(--border);border-radius:20px;font-size:12px;color:var(--txt2)">✓ {{ $a->name }}</span>
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>

  <!-- Owner -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><h3>🧑 Propriétaire</h3></div>
      <div style="padding:20px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
          <div class="avatar" style="width:44px;height:44px;font-size:18px">{{ strtoupper(substr($property->owner->name ?? 'O',0,1)) }}</div>
          <div>
            <div style="font-weight:700">{{ $property->owner->name ?? '—' }}</div>
            <div style="font-size:12px;color:var(--txt3)">{{ $property->owner->phone ?? '' }}</div>
            <div style="font-size:12px;color:var(--txt3)">{{ $property->owner->email ?? '' }}</div>
          </div>
        </div>
        <a href="{{ route('admin.users.show', $property->owner_id) }}" class="btn btn-outline btn-sm" style="width:100%">Voir le profil</a>
      </div>
    </div>

    <!-- Stats -->
    <div class="card">
      <div class="card-header"><h3>📊 Statistiques</h3></div>
      <div style="padding:16px 20px">
        <div style="margin-bottom:12px;display:flex;justify-content:space-between">
          <span style="font-size:13px;color:var(--txt2)">Réservations totales</span>
          <strong>{{ $property->bookings->count() }}</strong>
        </div>
        <div style="margin-bottom:12px;display:flex;justify-content:space-between">
          <span style="font-size:13px;color:var(--txt2)">Confirmées</span>
          <strong style="color:var(--green)">{{ $property->bookings->where('status','confirmé')->count() }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="font-size:13px;color:var(--txt2)">Revenu total</span>
          <strong style="color:var(--gold)">{{ number_format($property->bookings->sum('total_amount'),0,',',' ') }} XAF</strong>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent bookings for this property -->
@if($property->bookings->count())
<div class="card">
  <div class="card-header"><h3>📅 Réservations récentes</h3></div>
  <table>
    <thead><tr><th>Référence</th><th>Client</th><th>Dates</th><th>Montant</th><th>Statut</th></tr></thead>
    <tbody>
    @foreach($property->bookings->take(5) as $b)
    <tr>
      <td><a href="{{ route('admin.bookings.show', $b->reference) }}" style="color:var(--gold);font-weight:600;text-decoration:none">{{ $b->reference }}</a></td>
      <td>{{ $b->user->name ?? '—' }}</td>
      <td style="font-size:12px">{{ $b->check_in->format('d/m/Y') }} → {{ $b->check_out->format('d/m/Y') }}</td>
      <td><strong>{{ number_format($b->total_amount,0,',',' ') }}</strong></td>
      <td><span class="badge-status {{ $b->status }}">{{ $b->status }}</span></td>
    </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection