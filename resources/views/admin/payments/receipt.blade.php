{{-- resources/views/admin/payments/receipt.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reçu #{{ $payment->reference ?? "PAY-{$payment->id}" }} — TholadImmo</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:    #0F1F3D;
      --navy2:   #1E3A8A;
      --blue1:   #1565C0;
      --blue2:   #2979FF;
      --gold:    #C9A84C;
      --green:   #1E8F5E;
      --bg-green:#F0FBF6;
      --grey:    #6B7280;
      --border:  #E5E7EB;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      color: var(--navy);
      background: #EEF2F8;
      padding: 40px 20px;
      min-height: 100vh;
    }

    .page-wrap { max-width: 720px; margin: 0 auto; }

    .receipt {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 8px 48px rgba(15,31,61,.14), 0 2px 8px rgba(15,31,61,.06);
    }

    /* ── Header ── */
    .receipt-header {
      background: var(--navy);
      padding: 26px 36px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo-wrap { display: flex; align-items: center; gap: 14px; }
    .logo-text-block { display: flex; flex-direction: column; gap: 2px; }
    .logo-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: 26px; font-weight: 700;
      color: #fff; letter-spacing: .5px; line-height: 1;
    }
    .logo-sub {
      font-size: 9px; color: rgba(255,255,255,.4);
      letter-spacing: 2.5px; text-transform: uppercase;
    }
    .header-right { text-align: right; }
    .badge-confirmed {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--green); color: #fff;
      padding: 6px 16px; border-radius: 24px;
      font-size: 12px; font-weight: 700; letter-spacing: .5px;
      margin-bottom: 8px;
    }
    .header-ref { font-size: 11px; color: rgba(255,255,255,.4); letter-spacing: .5px; }
    .header-ref strong { color: rgba(255,255,255,.75); font-family: monospace; font-size: 12px; }

    /* ── Titre bar ── */
    .receipt-title-bar {
      background: linear-gradient(135deg, #EEF4FF 0%, #F0FBF6 100%);
      border-bottom: 1px solid var(--border);
      padding: 18px 36px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .receipt-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 22px; font-weight: 700; color: var(--navy);
    }
    .receipt-date-badge {
      font-size: 12px; color: var(--grey);
      background: #fff; border: 1px solid var(--border);
      border-radius: 8px; padding: 6px 14px;
    }
    .receipt-date-badge strong { color: var(--navy); }

    /* ── Body ── */
    .receipt-body { padding: 28px 36px; }

    /* ── Info grid ── */
    .info-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      border: 1px solid var(--border); border-radius: 12px;
      overflow: hidden; margin-bottom: 22px;
    }
    .info-cell {
      padding: 14px 18px;
      border-right: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
    }
    .info-cell:nth-child(2n) { border-right: none; }
    .info-cell:nth-last-child(-n+2) { border-bottom: none; }
    .info-cell label {
      display: block; font-size: 10px; text-transform: uppercase;
      letter-spacing: 1px; color: var(--grey);
      margin-bottom: 4px; font-weight: 600;
    }
    .info-cell span { font-size: 14px; font-weight: 600; color: var(--navy); }
    .info-cell span.mono { font-family: 'Courier New', monospace; font-size: 13px; }
    .method-dot {
      display: inline-block; width: 10px; height: 10px;
      border-radius: 50%; margin-right: 4px; vertical-align: middle;
    }

    /* ── Montant ── */
    .amount-block {
      background: var(--navy); border-radius: 14px;
      padding: 22px 28px; display: flex;
      justify-content: space-between; align-items: center;
      margin-bottom: 22px;
    }
    .amount-label { color: rgba(255,255,255,.6); font-size: 13px; margin-bottom: 4px; }
    .amount-detail { font-size: 11px; color: rgba(255,255,255,.35); margin-top: 4px; }
    .amount-value {
      font-family: 'Cormorant Garamond', serif;
      font-size: 34px; font-weight: 700; color: var(--gold); letter-spacing: 1px;
    }
    .amount-currency { font-size: 16px; color: rgba(201,168,76,.7); font-weight: 600; margin-left: 6px; }

    /* ── Propriété ── */
    .property-block {
      border: 1px solid #FDE68A; background: #FFFBEB;
      border-radius: 14px; padding: 20px 24px; margin-bottom: 22px;
    }
    .property-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 4px; }
    .property-name { font-size: 17px; font-weight: 700; color: var(--navy); margin-bottom: 3px; }
    .property-city { font-size: 12px; color: #92400E; }
    .property-badge {
      background: #FEF3C7; border: 1px solid #FDE68A; color: #92400E;
      font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
    }
    .dates-grid {
      display: grid; grid-template-columns: 1fr 1fr 1fr;
      gap: 10px; margin-top: 14px;
    }
    .date-cell {
      background: rgba(255,255,255,.7); border: 1px solid #FDE68A;
      border-radius: 10px; padding: 10px 14px;
    }
    .date-cell label {
      font-size: 9px; text-transform: uppercase; letter-spacing: 1px;
      color: #92400E; display: block; margin-bottom: 4px; font-weight: 700;
    }
    .date-cell span { font-size: 13px; font-weight: 600; color: var(--navy); }
    .owner-line { margin-top: 12px; font-size: 12px; color: #92400E; }

    /* ── Booking refs ── */
    .booking-refs {
      display: grid; grid-template-columns: 1fr 1fr 1fr;
      gap: 12px; margin-bottom: 22px;
    }
    .ref-cell {
      background: #F8FAFF; border: 1px solid var(--border);
      border-radius: 10px; padding: 14px 16px;
    }
    .ref-cell label {
      font-size: 10px; text-transform: uppercase; letter-spacing: .8px;
      color: var(--grey); display: block; margin-bottom: 5px; font-weight: 600;
    }
    .ref-cell span { font-size: 13px; font-weight: 700; color: var(--navy); }
    .status-confirmed { color: var(--green) !important; }
    .status-pending   { color: #EA580C !important; }
    .status-cancelled { color: #EF4444 !important; }

    /* ── Note admin ── */
    .note-block {
      background: #EFF6FF; border: 1px solid #BFDBFE;
      border-radius: 10px; padding: 14px 18px;
      margin-bottom: 22px; font-size: 13px; color: var(--navy2);
    }
    .note-block strong {
      display: block; margin-bottom: 4px;
      font-size: 10px; text-transform: uppercase;
      letter-spacing: .8px; color: var(--blue2);
    }

    /* ── Divider ── */
    .divider { border: none; border-top: 1px dashed var(--border); margin: 16px 0; }

    /* ── Authenticity ── */
    .authenticity-bar {
      display: flex; align-items: center; gap: 14px;
      background: #F8FAFF; border: 1px solid var(--border);
      border-radius: 12px; padding: 14px 18px;
    }
    .auth-icon { font-size: 26px; flex-shrink: 0; }
    .auth-text { font-size: 12px; color: var(--grey); line-height: 1.65; }
    .auth-text strong { color: var(--navy); }

    /* ── Footer ── */
    .receipt-footer {
      background: var(--navy);
      padding: 18px 36px;
      display: flex; justify-content: space-between; align-items: center;
      margin-top: 0;
    }
    .footer-left { font-size: 11px; color: rgba(255,255,255,.4); line-height: 1.8; }
    .footer-left strong { color: rgba(255,255,255,.7); }
    .footer-stamp {
      font-family: 'Cormorant Garamond', serif;
      font-size: 13px; color: var(--gold); opacity: .75;
    }
    .footer-ref { font-size: 10px; color: rgba(255,255,255,.25); margin-top: 2px; }

    /* ── Boutons ── */
    .print-actions {
      display: flex; gap: 12px; justify-content: center; margin-top: 28px;
    }
    .print-btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 12px 30px; background: var(--navy); color: var(--gold);
      border: none; border-radius: 10px; font-size: 14px; font-weight: 600;
      cursor: pointer; font-family: 'DM Sans', sans-serif; letter-spacing: .3px;
      transition: opacity .2s;
    }
    .print-btn:hover { opacity: .85; }
    .close-btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 12px 24px; background: #fff; color: var(--grey);
      border: 1.5px solid var(--border); border-radius: 10px;
      font-size: 14px; font-weight: 600; cursor: pointer;
      font-family: 'DM Sans', sans-serif; transition: border-color .2s;
    }
    .close-btn:hover { border-color: var(--navy); color: var(--navy); }

    @media print {
      body { padding: 0; background: #fff; }
      .print-actions { display: none !important; }
      .receipt { border-radius: 0; box-shadow: none; }
    }
  </style>
</head>
<body>

@php
  $pricePeriod   = $payment->booking?->property?->price_period ?? 'nuit';
  $nights        = $payment->booking?->nights ?? 1;
  $isHourly      = $pricePeriod === 'heure';
  $durationLabel = match($pricePeriod) {
    'heure'   => $nights <= 1 ? 'heure'   : 'heures',
    'jour'    => $nights <= 1 ? 'jour'    : 'jours',
    'semaine' => $nights <= 1 ? 'semaine' : 'semaines',
    'mois'    => 'mois',
    'an'      => $nights <= 1 ? 'an'      : 'ans',
    default   => $nights <= 1 ? 'nuit'    : 'nuits',
  };
  $methodColors = [
    'mtn_momo'     => '#FFCC00',
    'airtel_money' => '#EF4444',
    'orange_money' => '#FF6600',
    'virement'     => '#3B82F6',
  ];
  $methodColor = $methodColors[$payment->method] ?? '#9CA3AF';
  $statusClass = match($payment->booking?->status ?? '') {
    'confirmé'   => 'status-confirmed',
    'en_attente' => 'status-pending',
    'annulé'     => 'status-cancelled',
    default      => '',
  };
@endphp

<div class="page-wrap">
<div class="receipt">

  {{-- ── HEADER ── --}}
  <div class="receipt-header">
    <div class="logo-wrap">
      {{-- Logo SVG Tholad : 4 parallelogrammes --}}
      <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
        <polygon points="22,2 50,2 44,16 16,16" fill="#2979FF"/>
        <polygon points="2,20 30,20 24,32 2,32" fill="#2979FF"/>
        <polygon points="32,20 50,20 50,32 26,32" fill="#1565C0"/>
        <polygon points="16,35 44,35 38,50 16,50" fill="#1565C0"/>
      </svg>
      <div class="logo-text-block">
        <span class="logo-name">TholadImmo</span>
        <span class="logo-sub">Tholad Group</span>
      </div>
    </div>
    <div class="header-right">
      <div class="badge-confirmed">✅ Paiement confirmé</div>
      <div class="header-ref">Réf. : <strong>{{ $payment->reference ?? "PAY-{$payment->id}" }}</strong></div>
    </div>
  </div>

  {{-- ── TITRE ── --}}
  <div class="receipt-title-bar">
    <div class="receipt-title">Reçu de paiement</div>
    <div class="receipt-date-badge">
      Validé le <strong>{{ ($payment->verified_at ?? $payment->paid_at)?->format('d/m/Y à H:i') ?? '—' }}</strong>
    </div>
  </div>

  {{-- ── BODY ── --}}
  <div class="receipt-body">

    {{-- Infos client & paiement --}}
    <div class="info-grid">
      <div class="info-cell">
        <label>Client</label>
        <span>{{ $payment->user->name ?? '—' }}</span>
      </div>
      <div class="info-cell">
        <label>Téléphone</label>
        <span>{{ $payment->phone ?? $payment->user?->phone ?? '—' }}</span>
      </div>
      <div class="info-cell">
        <label>Email</label>
        <span>{{ $payment->user?->email ?? '—' }}</span>
      </div>
      <div class="info-cell">
        <label>Méthode de paiement</label>
        <span>
          <span class="method-dot" style="background:{{ $methodColor }}"></span>
          {{ $payment->method_emoji ?? '' }} {{ $payment->method_label ?? $payment->method }}
        </span>
      </div>
      <div class="info-cell">
        <label>ID Transaction opérateur</label>
        <span class="mono">{{ $payment->provider_ref ?? '—' }}</span>
      </div>
      <div class="info-cell">
        <label>Date de paiement</label>
        <span>{{ $payment->paid_at?->format('d/m/Y à H:i') ?? '—' }}</span>
      </div>
      <div class="info-cell">
        <label>Document émis le</label>
        <span>{{ $payment->created_at->format('d/m/Y à H:i') }}</span>
      </div>
      <div class="info-cell">
        <label>Statut paiement</label>
        <span style="color:var(--green);font-weight:700">
          {{ $payment->status_label ?? $payment->status }}
        </span>
      </div>
    </div>

    {{-- Montant --}}
    <div class="amount-block">
      <div>
        <div class="amount-label">Montant total payé</div>
        <div class="amount-detail">
          Base : {{ number_format($payment->booking?->base_amount ?? $payment->amount, 0, ',', ' ') }} XAF
          @if(($payment->booking?->fees_amount ?? 0) > 0)
            &nbsp;+&nbsp; Frais de service : {{ number_format($payment->booking->fees_amount, 0, ',', ' ') }} XAF
          @endif
        </div>
      </div>
      <div>
        <span class="amount-value">{{ number_format($payment->amount, 0, ',', ' ') }}</span>
        <span class="amount-currency">{{ $payment->currency ?? 'XAF' }}</span>
      </div>
    </div>

    {{-- Propriété --}}
    @if($payment->booking?->property)
    <div class="property-block">
      <div class="property-header">
        <div>
          <div class="property-name">{{ $payment->booking->property->title }}</div>
          <div class="property-city">
            📍 {{ $payment->booking->property->city ?? '—' }}
            @if($payment->booking->property->district), {{ $payment->booking->property->district }}@endif
          </div>
        </div>
        <span class="property-badge">{{ ucfirst($pricePeriod) }}</span>
      </div>

      <hr class="divider" style="border-color:#FDE68A">

      <div class="dates-grid">
        <div class="date-cell">
          <label>📅 Arrivée</label>
          <span>{{ $isHourly ? $payment->booking->check_in?->format('d/m/Y H:i') : $payment->booking->check_in?->format('d/m/Y') }}</span>
        </div>
        <div class="date-cell">
          <label>📅 Départ</label>
          <span>{{ $isHourly ? $payment->booking->check_out?->format('d/m/Y H:i') : $payment->booking->check_out?->format('d/m/Y') }}</span>
        </div>
        <div class="date-cell">
          <label>⏱ Durée &nbsp;·&nbsp; 👥 Voyageurs</label>
          <span>{{ $nights }} {{ $durationLabel }} · {{ $payment->booking->guests }} pers.</span>
        </div>
      </div>

      @if($payment->booking->property?->owner)
      <div class="owner-line">
        🏠 Propriétaire : <strong>{{ $payment->booking->property->owner->name }}</strong>
        @if($payment->booking->property->owner->phone)
          &nbsp;·&nbsp; {{ $payment->booking->property->owner->phone }}
        @endif
      </div>
      @endif
    </div>
    @endif

    {{-- Refs réservation --}}
    @if($payment->booking)
    <div class="booking-refs">
      <div class="ref-cell">
        <label>Référence réservation</label>
        <span style="font-family:monospace">{{ $payment->booking->reference ?? "BK-{$payment->booking_id}" }}</span>
      </div>
      <div class="ref-cell">
        <label>Statut réservation</label>
        <span class="{{ $statusClass }}">{{ $payment->booking->status_label ?? $payment->booking->status ?? '—' }}</span>
      </div>
      <div class="ref-cell">
        <label>Nombre de voyageurs</label>
        <span>{{ $payment->booking->guests }} personne{{ $payment->booking->guests > 1 ? 's' : '' }}</span>
      </div>
    </div>
    @endif

    {{-- Note admin --}}
    @if($payment->admin_note)
    <div class="note-block">
      <strong>Note administrative</strong>
      {{ $payment->admin_note }}
    </div>
    @endif

    {{-- Authenticité --}}
    <div class="authenticity-bar">
      <div class="auth-icon">🔒</div>
      <div class="auth-text">
        Ce reçu est généré automatiquement par <strong>TholadImmo — Tholad Group</strong> et vaut preuve officielle de paiement.<br>
        En cas de litige, conservez ce document et l'<strong>ID transaction opérateur</strong>. Document valide sans signature ni cachet.
      </div>
    </div>

  </div>{{-- /body --}}

  {{-- ── FOOTER ── --}}
  <div class="receipt-footer">
    <div class="footer-left">
      <strong>TholadImmo</strong> — Tholad Group<br>
      Brazzaville &amp; Pointe-Noire, République du Congo<br>
      Reçu généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
    <div style="text-align:right">
      <div class="footer-stamp">Document officiel</div>
      <div class="footer-ref">{{ $payment->reference ?? "PAY-{$payment->id}" }}</div>
    </div>
  </div>

</div>{{-- /receipt --}}

<div class="print-actions">
  <button class="close-btn" onclick="window.close()">✕ Fermer</button>
  <button class="print-btn" onclick="window.print()">🖨️ Imprimer / Télécharger PDF</button>
</div>

</div>{{-- /page-wrap --}}

<script>
  window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
