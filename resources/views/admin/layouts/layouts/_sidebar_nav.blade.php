{{-- =====================================================================
   SECTION À REMPLACER dans resources/views/admin/layouts/app.blade.php
   Remplacer uniquement le bloc <nav class="sidebar-menu">...</nav>
   ===================================================================== --}}

  <nav class="sidebar-menu">
    <div class="menu-section">Principal</div>
    <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <i class="fas fa-chart-pie"></i> Dashboard
    </a>

    <div class="menu-section">Gestion</div>

    {{-- Propriétés --}}
    <a href="{{ route('admin.properties.index') }}" class="menu-item {{ request()->routeIs('admin.properties.*') ? 'active' : '' }}">
      <i class="fas fa-building"></i> Propriétés
    </a>

    {{-- Propriétaires --}}
    <a href="{{ route('admin.owners.index') }}" class="menu-item {{ request()->routeIs('admin.owners.*') ? 'active' : '' }}">
      <i class="fas fa-home"></i> Propriétaires
      @php $pendingOwners = \App\Models\OwnerProfile::where('status','en_attente')->count(); @endphp
      @if($pendingOwners > 0)<span class="badge">{{ $pendingOwners }}</span>@endif
    </a>

    {{-- Réservations --}}
    <a href="{{ route('admin.bookings.index') }}" class="menu-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
      <i class="fas fa-calendar-check"></i> Réservations
      @php $pending = \App\Models\Booking::where('status','en_attente')->count(); @endphp
      @if($pending > 0)<span class="badge">{{ $pending }}</span>@endif
    </a>

    {{-- Paiements --}}
    <a href="{{ route('admin.payments.index') }}" class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
      <i class="fas fa-credit-card"></i> Paiements
    </a>

    {{-- Utilisateurs --}}
    <a href="{{ route('admin.users.index') }}" class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
      <i class="fas fa-users"></i> Utilisateurs
    </a>

    {{-- Avis --}}
    <a href="{{ route('admin.reviews.index') }}" class="menu-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
      <i class="fas fa-star"></i> Avis
    </a>

    <div class="menu-section">TholadImmo</div>

    {{-- Agents --}}
    <a href="{{ route('admin.agents.index') }}" class="menu-item {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}">
      <i class="fas fa-id-badge"></i> Agents
    </a>

    {{-- Stocks --}}
    <a href="{{ route('admin.stock.index') }}" class="menu-item {{ request()->routeIs('admin.stock.*') ? 'active' : '' }}">
      <i class="fas fa-boxes"></i> Stocks
      @php
        $stockAlerts = 0;
        try { $stockAlerts = \App\Models\StockAlert::where('is_resolved',false)->where('is_read',false)->count(); } catch(\Exception $e) {}
      @endphp
      @if($stockAlerts > 0)
        <span class="badge" style="background:#F59E0B;">{{ $stockAlerts }}</span>
      @endif
    </a>

    <div class="menu-section">Business</div>

    {{-- Comptabilité --}}
    <a href="{{ route('admin.accounting.index') }}" class="menu-item {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}">
      <i class="fas fa-coins"></i> Comptabilité
    </a>

    {{-- Support --}}
    <a href="{{ route('admin.support.index') }}" class="menu-item {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
      <i class="fas fa-headset"></i> Support
      @php $open = \App\Models\SupportTicket::where('status','ouvert')->count(); @endphp
      @if($open > 0)<span class="badge">{{ $open }}</span>@endif
    </a>

    <div class="menu-section">Système</div>
    <a href="{{ route('admin.settings') }}" class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
      <i class="fas fa-cog"></i> Paramètres
    </a>
    <a href="{{ route('admin.logout') }}" class="menu-item"
       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display:none;">@csrf</form>
  </nav>
