{{-- resources/views/admin/messages/index.blade.php --}}
{{-- Vue conversations côté admin — permet de voir et répondre aux messages clients --}}
@extends('admin.layouts.app')
@section('title', 'Conversations')
@section('content')

<div style="display:flex;gap:0;height:calc(100vh - 130px);background:var(--white);border-radius:16px;border:1px solid var(--border);overflow:hidden">

  {{-- ── Colonne liste conversations ──────────────────────────────────── --}}
  <div id="conv-list" style="width:320px;min-width:280px;border-right:1px solid var(--border);overflow-y:auto;flex-shrink:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;font-size:15px;background:var(--bg)">
      💬 Conversations ({{ $conversations->count() }})
    </div>

    @forelse($conversations as $conv)
      @php
        $me = auth()->guard('admin')->user();
        $partner = null;
        // Le partenaire est l'utilisateur non-admin
        if ($conv->user1 && $conv->user1->role !== 'admin') {
            $partner = $conv->user1;
            $unread  = $conv->user2_unread ?? 0;
        } else {
            $partner = $conv->user2;
            $unread  = $conv->user1_unread ?? 0;
        }
        $initial = strtoupper(substr($partner->name ?? 'U', 0, 1));
      @endphp
      <a href="{{ route('admin.messages.show', $conv->id) }}"
         style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;
                {{ request()->route('id') == $conv->id ? 'background:var(--bg);border-left:3px solid var(--blue)' : 'border-left:3px solid transparent' }};
                transition:background .15s"
         onmouseover="this.style.background='var(--bg)'"
         onmouseout="this.style.background='{{ request()->route('id') == $conv->id ? 'var(--bg)' : '' }}'">
        <div class="avatar" style="width:42px;height:42px;font-size:15px;flex-shrink:0">{{ $initial }}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-weight:700;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $partner->name ?? '—' }}</span>
            <span style="font-size:10px;color:var(--txt3);white-space:nowrap;margin-left:8px">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:2px">
            <span style="font-size:12px;color:var(--txt3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px">
              {{ Str::limit($conv->last_message ?? 'Pas de message', 40) }}
            </span>
            @if($unread > 0)
              <span style="background:var(--blue);color:#fff;border-radius:10px;padding:2px 7px;font-size:10px;font-weight:700;margin-left:6px;flex-shrink:0">{{ $unread }}</span>
            @endif
          </div>
          <div style="font-size:10px;color:var(--txt3);margin-top:2px">{{ $partner->phone ?? $partner->email ?? '' }}</div>
        </div>
      </a>
    @empty
      <div style="padding:40px;text-align:center;color:var(--txt3)">
        💬 Aucune conversation pour le moment
      </div>
    @endforelse
  </div>

  {{-- ── Zone principale (message ou placeholder) ──────────────────────── --}}
  <div style="flex:1;display:flex;flex-direction:column;overflow:hidden">

    @if(isset($activeConv))
      @php
        $partner2 = null;
        if ($activeConv->user1 && $activeConv->user1->role !== 'admin') {
            $partner2 = $activeConv->user1;
        } else {
            $partner2 = $activeConv->user2;
        }
        $adminUser = $activeConv->user1->role === 'admin' ? $activeConv->user1 : $activeConv->user2;
      @endphp

      {{-- Header conversation --}}
      <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;background:var(--white)">
        <div class="avatar" style="width:40px;height:40px">{{ strtoupper(substr($partner2->name ?? 'U', 0, 1)) }}</div>
        <div>
          <div style="font-weight:700;font-size:14px">{{ $partner2->name ?? '—' }}</div>
          <div style="font-size:12px;color:var(--txt3)">{{ $partner2->phone ?? '' }} • {{ $partner2->email ?? '' }}</div>
        </div>
        <div style="margin-left:auto;font-size:12px;color:var(--txt3)">
          Conversation #{{ $activeConv->id }}
        </div>
      </div>

      {{-- Messages --}}
      <div id="msg-area" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px;background:var(--bg)">
        @forelse($messages as $msg)
          @php
            $isMine = $msg->sender_id === ($adminUser->id ?? null);
          @endphp
          <div style="display:flex;justify-content:{{ $isMine ? 'flex-end' : 'flex-start' }}">
            <div style="max-width:65%;padding:10px 14px;border-radius:{{ $isMine ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};
                        background:{{ $isMine ? 'var(--navy2)' : 'var(--white)' }};
                        color:{{ $isMine ? '#fff' : 'var(--txt1)' }};
                        box-shadow:0 2px 8px rgba(0,0,0,.08);font-size:13px;line-height:1.5">
              {{ $msg->content }}
              <div style="font-size:10px;color:{{ $isMine ? 'rgba(255,255,255,.6)' : 'var(--txt3)' }};margin-top:4px;text-align:right">
                {{ $msg->created_at?->format('H:i') }}
                @if($isMine)
                  • {{ $msg->is_read ? '✓✓' : '✓' }}
                @endif
              </div>
            </div>
          </div>
        @empty
          <div style="text-align:center;color:var(--txt3);padding:40px">Aucun message dans cette conversation.</div>
        @endforelse
      </div>

      {{-- Zone de saisie --}}
      <div style="padding:16px 20px;border-top:1px solid var(--border);background:var(--white)">
        <form action="{{ route('admin.messages.reply', $activeConv->id) }}" method="POST"
              style="display:flex;gap:10px;align-items:flex-end">
          @csrf
          <textarea name="content" rows="2" placeholder="Répondre à {{ $partner2->name ?? 'ce client' }}..."
            style="flex:1;border:1px solid var(--border);border-radius:12px;padding:10px 14px;font-size:13px;font-family:inherit;resize:none;outline:none;transition:border .2s"
            onfocus="this.style.borderColor='var(--blue)'"
            onblur="this.style.borderColor='var(--border)'"
            required></textarea>
          <button type="submit" class="btn btn-gold" style="padding:10px 20px;height:fit-content">
            <i class="fas fa-paper-plane"></i> Envoyer
          </button>
        </form>
      </div>

    @else
      {{-- Placeholder aucune conv sélectionnée --}}
      <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--txt3)">
        <i class="fas fa-comments" style="font-size:48px;opacity:.3"></i>
        <p style="font-size:15px;font-weight:600">Sélectionnez une conversation</p>
        <p style="font-size:13px">Cliquez sur une conversation à gauche pour voir les messages.</p>
      </div>
    @endif

  </div>
</div>

<script>
  // Auto-scroll vers le bas des messages
  document.addEventListener('DOMContentLoaded', () => {
    const area = document.getElementById('msg-area');
    if (area) area.scrollTop = area.scrollHeight;
  });

  // Auto-refresh toutes les 10 secondes si une conversation est ouverte
  @if(isset($activeConv))
  setTimeout(() => window.location.reload(), 10000);
  @endif
</script>
@endsection
