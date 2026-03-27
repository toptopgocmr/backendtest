{{-- resources/views/admin/bookings/show.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Réservation ' . $booking->reference)
@section('content')
<div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
  <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline btn-sm">← Retour</a>
  <div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700">{{ $booking->reference }}</h2>
    <p style="color:var(--txt3);font-size:13px">Créée le {{ $booking->created_at->format('d/m/Y à H:i') }}</p>
  </div>
  <div style="margin-left:auto;display:flex;gap:10px">
    @if($booking->status === 'en_attente')
    <form action="{{ route('admin.bookings.confirm', $booking->reference) }}" method="POST">
      @csrf @method('PUT')
      <button type="submit" class="btn btn-success">✓ Confirmer</button>
    </form>
    @endif
    @if($booking->status === 'confirmé')
    <form action="{{ route('admin.bookings.complete', $booking->reference) }}" method="POST">
      @csrf @method('PUT')
      <button type="submit" class="btn btn-gold">✓ Terminer</button>
    </form>
    @endif
    <span class="badge-status {{ $booking->status }}" style="font-size:14px;padding:8px 16px">{{ $booking->status }}</span>
  </div>
</div>

<div class="grid-2" style="margin-bottom:20px">
  <!-- Client info -->
  <div class="card">
    <div class="card-header"><h3>👤 Client</h3></div>
    <div style="padding:20px">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
        <div class="avatar" style="width:50px;height:50px;font-size:20px">{{ strtoupper(substr($booking->user->name,0,1)) }}</div>
        <div>
          <div style="font-weight:700;font-size:16px">{{ $booking->user->name }}</div>
          <div style="font-size:13px;color:var(--txt3)">{{ $booking->user->phone }}</div>
          <div style="font-size:13px;color:var(--txt3)">{{ $booking->user->email ?? '—' }}</div>
        </div>
      </div>
      <div style="border-top:1px solid var(--border);padding-top:14px">
        <div style="font-size:12px;color:var(--txt3)">Invités</div>
        <div style="font-weight:700;font-size:18px">{{ $booking->guests }} personne(s)</div>
      </div>
      @if($booking->notes)
      <div style="margin-top:12px;padding:12px;background:var(--bg);border-radius:10px;font-size:13px;color:var(--txt2)">
        💬 <em>{{ $booking->notes }}</em>
      </div>
      @endif
    </div>
  </div>

  <!-- Dates & Amounts -->
  <div class="card">
    <div class="card-header"><h3>📅 Dates & Montants</h3></div>
    <div style="padding:20px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div style="padding:14px;background:var(--bg);border-radius:10px">
          <div style="font-size:11px;color:var(--txt3);margin-bottom:4px">ARRIVÉE</div>
          <div style="font-weight:700;color:var(--navy)">{{ $booking->check_in->format('d/m/Y') }}</div>
        </div>
        <div style="padding:14px;background:var(--bg);border-radius:10px">
          <div style="font-size:11px;color:var(--txt3);margin-bottom:4px">DÉPART</div>
          <div style="font-weight:700;color:var(--navy)">{{ $booking->check_out->format('d/m/Y') }}</div>
        </div>
      </div>
      <div style="font-size:13px;color:var(--txt3);margin-bottom:12px">⏱ {{ $booking->nights }} nuit(s)</div>
      <div style="border-top:1px solid var(--border);padding-top:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px">
          <span>Montant de base</span><span>{{ number_format($booking->base_amount,0,',',' ') }} {{ $booking->currency }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;color:var(--txt3)">
          <span>Frais de service (5%)</span><span>{{ number_format($booking->fees_amount,0,',',' ') }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;color:var(--gold)">
          <span>TOTAL</span><span>{{ number_format($booking->total_amount,0,',',' ') }} {{ $booking->currency }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Property info -->
  <div class="card">
    <div class="card-header"><h3>🏠 Propriété</h3></div>
    <div style="padding:20px">
      @if($booking->property)
        @if($booking->property->images->first())
          <img src="{{ $booking->property->images->first()->url }}" style="width:100%;height:160px;object-fit:cover;border-radius:12px;margin-bottom:14px">
        @endif
        <div style="font-weight:700;font-size:16px;margin-bottom:4px">{{ $booking->property->title }}</div>
        <div style="font-size:13px;color:var(--txt3);margin-bottom:12px">📍 {{ $booking->property->city }}, {{ $booking->property->district }}</div>
        <div style="display:flex;gap:12px;font-size:13px">
          <span>🛏 {{ $booking->property->bedrooms }} ch.</span>
          <span>🚿 {{ $booking->property->bathrooms }} sdb</span>
          <span>👥 max {{ $booking->property->max_guests }}</span>
        </div>
        <a href="{{ route('admin.properties.show', $booking->property->id) }}" class="btn btn-outline btn-sm" style="margin-top:14px">Voir la propriété</a>
      @else
        <p style="color:var(--txt3)">Propriété supprimée</p>
      @endif
    </div>
  </div>

  <!-- Payment info -->
  <div class="card">
    <div class="card-header"><h3>💳 Paiement</h3></div>
    <div style="padding:20px">
      @if($booking->payment)
        <div style="margin-bottom:14px">
          <div style="font-size:12px;color:var(--txt3)">Référence</div>
          <div style="font-weight:700">{{ $booking->payment->reference }}</div>
        </div>
        <div style="margin-bottom:14px">
          <div style="font-size:12px;color:var(--txt3)">Méthode</div>
          <div style="font-weight:600">{{ $booking->payment->method_label }}</div>
        </div>
        <div style="margin-bottom:14px">
          <div style="font-size:12px;color:var(--txt3)">Statut</div>
          <span class="badge-status {{ $booking->payment->status }}">{{ $booking->payment->status }}</span>
        </div>
        @if($booking->payment->paid_at)
        <div style="margin-bottom:14px">
          <div style="font-size:12px;color:var(--txt3)">Payé le</div>
          <div style="font-weight:600">{{ $booking->payment->paid_at->format('d/m/Y à H:i') }}</div>
        </div>
        @endif
        @if($booking->payment->isSuccess())
        <form action="{{ route('admin.payments.refund', $booking->payment->reference) }}" method="POST" onsubmit="return confirm('Rembourser ?')">
          @csrf
          <input type="hidden" name="reason" value="Remboursement admin">
          <button type="submit" class="btn btn-danger btn-sm">Rembourser</button>
        </form>
        @endif
      @else
        <div style="text-align:center;padding:30px;color:var(--txt3)">
          <div style="font-size:36px;margin-bottom:10px">💳</div>
          <div>Aucun paiement enregistré</div>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Cancellation -->
@if($booking->cancel_reason)
<div style="margin-top:20px" class="alert alert-error">
  <i class="fas fa-times-circle"></i>
  <div>
    <strong>Annulée le {{ $booking->cancelled_at?->format('d/m/Y') }}</strong><br>
    Raison : {{ $booking->cancel_reason }}
  </div>
</div>
@endif
@endsection
