{{-- resources/views/admin/support/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Support')
@section('content')
<h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;margin-bottom:20px">Tickets de support</h2>

<div class="card">
  <table>
    <thead><tr><th>Ticket</th><th>Client</th><th>Sujet</th><th>Catégorie</th><th>Priorité</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($tickets as $ticket)
    <tr>
      <td><strong style="color:var(--navy2)">#{{ $ticket->id }}</strong></td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="avatar">{{ strtoupper(substr($ticket->user->name ?? 'U',0,1)) }}</div>
          <div>
            <div style="font-weight:600;font-size:13px">{{ $ticket->user->name ?? '—' }}</div>
            <div style="font-size:11px;color:var(--txt3)">{{ $ticket->user->phone ?? '' }}</div>
          </div>
        </div>
      </td>
      <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ticket->subject }}</td>
      <td>
        @php $catIcons=['booking'=>'📅','payment'=>'💳','technical'=>'🔧','other'=>'❓']; @endphp
        {{ $catIcons[$ticket->category] ?? '❓' }} {{ $ticket->category }}
      </td>
      <td>
        @php $prioColors=['basse'=>'var(--txt3)','normale'=>'var(--blue)','haute'=>'var(--gold)','urgente'=>'var(--coral)']; @endphp
        <span style="color:{{ $prioColors[$ticket->priority] ?? '#666' }};font-weight:700;font-size:12px">{{ strtoupper($ticket->priority) }}</span>
      </td>
      <td>
        @php $statColors=['ouvert'=>'var(--coral)','en_cours'=>'var(--gold)','résolu'=>'var(--green)','fermé'=>'var(--txt3)']; @endphp
        <span style="color:{{ $statColors[$ticket->status] ?? '#666' }};font-weight:600;font-size:12px">{{ $ticket->status }}</span>
      </td>
      <td style="font-size:12px;color:var(--txt3)">{{ $ticket->created_at->diffForHumans() }}</td>
      <td>
        <a href="{{ route('admin.support.show', $ticket->id) }}" class="btn btn-gold btn-sm">Répondre</a>
      </td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--txt3)">🎉 Aucun ticket en attente</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="padding:16px 20px;border-top:1px solid var(--border)">{{ $tickets->links() }}</div>
</div>
@endsection
