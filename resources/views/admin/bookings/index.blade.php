{{-- resources/views/admin/bookings/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Réservations')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700">Gestion des réservations</h2>
    <p style="color:var(--txt3);font-size:13px">{{ $bookings->total() }} réservations au total</p>
  </div>
</div>

<!-- Status tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  @foreach([''=>'Toutes', 'en_attente'=>'En attente', 'confirmé'=>'Confirmées', 'terminé'=>'Terminées', 'annulé'=>'Annulées'] as $val=>$label)
  <a href="{{ route('admin.bookings.index', ['status'=>$val]) }}" class="btn {{ request('status')===$val ? 'btn-gold' : 'btn-outline' }}">{{ $label }}</a>
  @endforeach
</div>

<div class="card">
  <table>
    <thead><tr><th>Référence</th><th>Client</th><th>Propriété</th><th>Dates</th><th>Montant</th><th>Paiement</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($bookings as $b)
    <tr>
      <td><strong style="color:var(--gold)">{{ $b->reference }}</strong><br><span style="font-size:11px;color:var(--txt3)">{{ $b->created_at->format('d/m/Y') }}</span></td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="avatar">{{ strtoupper(substr($b->user->name,0,1)) }}</div>
          <div>
            <div style="font-weight:600">{{ $b->user->name }}</div>
            <div style="font-size:11px;color:var(--txt3)">{{ $b->user->phone }}</div>
          </div>
        </div>
      </td>
      <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $b->property->title ?? '—' }}</td>
      <td style="font-size:12px">
        <i class="fas fa-calendar" style="color:var(--gold)"></i> {{ $b->check_in->format('d/m/Y') }}<br>
        <i class="fas fa-calendar-check" style="color:var(--green)"></i> {{ $b->check_out->format('d/m/Y') }}<br>
        <span style="color:var(--txt3)">{{ $b->nights }} nuit(s)</span>
      </td>
      <td><strong>{{ number_format($b->total_amount,0,',',' ') }} {{ $b->currency }}</strong></td>
      <td>
        @if($b->payment)
          <span class="badge-status {{ $b->payment->status }}">{{ $b->payment->status }}</span>
        @else
          <span style="color:var(--txt3);font-size:12px">Non payé</span>
        @endif
      </td>
      <td><span class="badge-status {{ $b->status }}">{{ $b->status }}</span></td>
      <td>
        <div style="display:flex;gap:6px">
          @if($b->status === 'en_attente')
          <form action="{{ route('admin.bookings.confirm', $b->reference) }}" method="POST">
            @csrf @method('PUT')
            <button type="submit" class="btn btn-success btn-sm" title="Confirmer"><i class="fas fa-check"></i></button>
          </form>
          @endif
          <a href="{{ route('admin.bookings.show', $b->reference) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
        </div>
      </td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--txt3)">📅 Aucune réservation</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="padding:16px 20px;border-top:1px solid var(--border)">{{ $bookings->withQueryString()->links() }}</div>
</div>
@endsection
