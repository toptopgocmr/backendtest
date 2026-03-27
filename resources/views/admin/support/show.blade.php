{{-- resources/views/admin/support/show.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Ticket #' . $ticket->id)
@section('content')
<div style="max-width:700px">
  <a href="{{ route('admin.support.index') }}" class="btn btn-outline btn-sm" style="margin-bottom:20px">
    ← Retour aux tickets
  </a>

  <!-- Ticket info -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <h3>🎫 Ticket #{{ $ticket->id }} — {{ $ticket->subject }}</h3>
      @php $prioColors=['basse'=>'var(--txt3)','normale'=>'var(--blue)','haute'=>'var(--gold)','urgente'=>'var(--coral)']; @endphp
      <span style="color:{{ $prioColors[$ticket->priority] ?? '#666' }};font-weight:700;font-size:12px;padding:4px 10px;border-radius:20px;background:rgba(0,0,0,.05)">{{ strtoupper($ticket->priority) }}</span>
    </div>
    <div style="padding:20px">
      <!-- Client info -->
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:14px;background:var(--bg);border-radius:12px">
        <div class="avatar" style="width:44px;height:44px;font-size:16px">{{ strtoupper(substr($ticket->user->name ?? 'U',0,1)) }}</div>
        <div>
          <div style="font-weight:700">{{ $ticket->user->name ?? '—' }}</div>
          <div style="font-size:12px;color:var(--txt3)">{{ $ticket->user->phone ?? '' }} • {{ $ticket->user->email ?? '' }}</div>
          <div style="font-size:12px;color:var(--txt3)">Envoyé {{ $ticket->created_at->diffForHumans() }}</div>
        </div>
        <div style="margin-left:auto">
          @php $catIcons=['booking'=>'📅','payment'=>'💳','technical'=>'🔧','other'=>'❓']; @endphp
          <span style="font-size:12px;background:var(--border);padding:4px 10px;border-radius:20px">
            {{ $catIcons[$ticket->category] ?? '❓' }} {{ $ticket->category }}
          </span>
        </div>
      </div>

      <!-- Message -->
      <div style="background:var(--white);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:20px">
        <div style="font-size:12px;color:var(--txt3);margin-bottom:8px;font-weight:600">MESSAGE DU CLIENT</div>
        <p style="color:var(--txt2);line-height:1.7">{{ $ticket->message }}</p>
      </div>

      <!-- Admin reply (if exists) -->
      @if($ticket->admin_reply)
      <div style="background:var(--goldpal);border:1px solid rgba(184,134,11,.2);border-radius:12px;padding:18px;margin-bottom:20px">
        <div style="font-size:12px;color:var(--gold);margin-bottom:8px;font-weight:700">VOTRE RÉPONSE — {{ $ticket->replied_at?->format('d/m/Y H:i') }}</div>
        <p style="color:var(--txt2);line-height:1.7">{{ $ticket->admin_reply }}</p>
      </div>
      @endif

      <!-- Reply form -->
      @if(!in_array($ticket->status, ['résolu','fermé']))
      <form action="{{ route('admin.support.reply', $ticket->id) }}" method="POST">
        @csrf
        <div class="form-group">
          <label>Votre réponse</label>
          <textarea name="reply" class="form-control" rows="5" placeholder="Rédigez votre réponse..." required style="resize:vertical"></textarea>
        </div>
        <div style="display:flex;gap:10px">
          <button type="submit" class="btn btn-gold"><i class="fas fa-paper-plane"></i> Envoyer la réponse</button>
          <a href="{{ route('admin.support.close', $ticket->id) }}" class="btn btn-outline"
             onclick="event.preventDefault(); if(confirm('Marquer comme résolu ?')) { fetch(this.href, {method:'PUT', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}}).then(()=>window.location.reload()) }">
            ✓ Marquer résolu
          </a>
        </div>
      </form>
      @else
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> Ce ticket est {{ $ticket->status }}.</div>
      @endif
    </div>
  </div>
</div>
@endsection
