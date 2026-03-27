<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Dashboard') — ImmoStay Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
:root {
  --navy:#0F1F3D; --navy2:#1E3A8A; --gold:#B8860B; --gold2:#D4A017;
  --goldpal:#FDF3DC; --green:#10B981; --coral:#EF4444; --blue:#3B82F6;
  --bg:#F8F6F1; --white:#fff; --border:#E8E0D0;
  --txt:#0F1F3D; --txt2:#4A4A4A; --txt3:#9E9E9E; --sidebar:260px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--txt);display:flex;min-height:100vh;}

/* Sidebar */
.sidebar{width:var(--sidebar);background:var(--navy);position:fixed;top:0;left:0;height:100vh;display:flex;flex-direction:column;z-index:100;}
.sidebar-logo{padding:28px 24px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:12px;}
.logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--gold2),var(--gold));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.sidebar-logo h1{font-family:'Cormorant Garamond',serif;font-size:22px;color:#fff;font-weight:700;}
.sidebar-logo span{font-size:10px;color:rgba(255,255,255,.4);letter-spacing:2px;text-transform:uppercase;display:block;margin-top:1px;}
.sidebar-menu{flex:1;padding:16px 12px;overflow-y:auto;}
.menu-section{font-size:10px;color:rgba(255,255,255,.3);letter-spacing:2px;text-transform:uppercase;padding:16px 12px 8px;}
.menu-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;color:rgba(255,255,255,.6);text-decoration:none;font-size:13.5px;font-weight:500;transition:.2s;cursor:pointer;margin-bottom:2px;}
.menu-item:hover{background:rgba(255,255,255,.07);color:#fff;}
.menu-item.active{background:linear-gradient(135deg,rgba(0,71,255,.25),rgba(26,0,153,.2));color:#60A5FA;border:1px solid rgba(0,71,255,.25);}
.menu-item i{width:18px;text-align:center;font-size:14px;}
.badge{background:var(--coral);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-left:auto;}
.sidebar-bottom{padding:16px;border-top:1px solid rgba(255,255,255,.08);}
.admin-info{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;background:rgba(255,255,255,.05);}
.admin-avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--gold2),var(--gold));border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:14px;}
.admin-info-text{flex:1;}
.admin-name{font-size:13px;font-weight:600;color:#fff;}
.admin-role{font-size:11px;color:rgba(255,255,255,.4);}

/* Main */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh;}
.topbar{background:var(--white);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50;}
.topbar-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--navy);flex:1;}
.topbar-actions{display:flex;align-items:center;gap:12px;}
.topbar-btn{width:38px;height:38px;border-radius:10px;border:1px solid var(--border);background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--txt2);cursor:pointer;position:relative;transition:.2s;}
.topbar-btn:hover{border-color:var(--gold);color:var(--gold);}
.notif-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--coral);border-radius:50%;border:2px solid #fff;}
.content{padding:28px;flex:1;}

/* Cards */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:var(--white);border:1px solid var(--border);border-radius:16px;padding:20px 22px;transition:.2s;}
.stat-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.06);}
.stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:14px;}
.stat-value{font-size:26px;font-weight:700;color:var(--navy);font-family:'Cormorant Garamond',serif;}
.stat-label{font-size:12px;color:var(--txt3);margin-top:2px;}
.stat-change{font-size:12px;margin-top:6px;font-weight:600;}
.stat-change.up{color:var(--green);}
.stat-change.down{color:var(--coral);}

/* Tables */
.card{background:var(--white);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.card-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.card-header h3{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;color:var(--navy);flex:1;}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:.2s;text-decoration:none;}
.btn-gold{background:linear-gradient(135deg,var(--gold2),var(--gold));color:#fff;}
.btn-gold:hover{opacity:.9;}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--txt2);}
.btn-outline:hover{border-color:var(--gold);color:var(--gold);}
.btn-sm{padding:6px 13px;font-size:12px;}
.btn-danger{background:#FEF2F2;color:var(--coral);border:1px solid #FECACA;}
.btn-success{background:#ECFDF5;color:var(--green);border:1px solid #A7F3D0;}

table{width:100%;border-collapse:collapse;}
th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);background:var(--bg);}
td{padding:14px 16px;font-size:13.5px;border-bottom:1px solid var(--border);}
tr:last-child td{border-bottom:none;}
tr:hover td{background:var(--bg);}

/* Badges */
.badge-status{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-status.confirmé,.badge-status.actif,.badge-status.succès{background:#ECFDF5;color:var(--green);}
.badge-status.en_attente{background:#FFF7ED;color:#EA580C;}
.badge-status.annulé,.badge-status.échoué{background:#FEF2F2;color:var(--coral);}
.badge-status.terminé{background:#F3F4F6;color:#6B7280;}
.badge-status.disponible{background:#EFF6FF;color:var(--blue);}

/* Grid 2col */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;}

/* Avatar */
.avatar{width:34px;height:34px;border-radius:10px;background:var(--goldpal);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--gold);font-size:13px;}
.avatar img{width:100%;height:100%;object-fit:cover;border-radius:10px;}

/* Alert */
.alert{padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.alert-success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;}
.alert-error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA;}

/* Form */
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--txt2);margin-bottom:6px;}
.form-control{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;background:var(--white);color:var(--txt);transition:.2s;outline:none;}
.form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(184,134,11,.1);}
select.form-control{cursor:pointer;}

/* Scrollbar */
::-webkit-scrollbar{width:5px;height:5px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:10px;}
</style>
@yield('extra_css')
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🏠</div>
    <div>
      <h1>ImmoStay</h1>
      <span>Tholad Group</span>
    </div>
  </div>

  <nav class="sidebar-menu">
    <div class="menu-section">Principal</div>
    <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <i class="fas fa-chart-pie"></i> Dashboard
    </a>

    <div class="menu-section">Gestion</div>
    <a href="{{ route('admin.properties.index') }}" class="menu-item {{ request()->routeIs('admin.properties.*') ? 'active' : '' }}">
      <i class="fas fa-building"></i> Propriétés
    </a>
    <a href="{{ route('admin.bookings.index') }}" class="menu-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
      <i class="fas fa-calendar-check"></i> Réservations
      @php $pending = \App\Models\Booking::where('status','en_attente')->count(); @endphp
      @if($pending > 0)<span class="badge">{{ $pending }}</span>@endif
    </a>
    <a href="{{ route('admin.payments.index') }}" class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
      <i class="fas fa-credit-card"></i> Paiements
    </a>
    <a href="{{ route('admin.users.index') }}" class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
      <i class="fas fa-users"></i> Utilisateurs
    </a>
    <a href="{{ route('admin.reviews.index') }}" class="menu-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
      <i class="fas fa-star"></i> Avis
    </a>

    <div class="menu-section">Business</div>
    <a href="{{ route('admin.accounting.index') }}" class="menu-item {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}">
      <i class="fas fa-coins"></i> Comptabilité
    </a>
    <a href="{{ route('admin.support.index') }}" class="menu-item {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
      <i class="fas fa-headset"></i> Support
      @php $open = \App\Models\SupportTicket::where('status','ouvert')->count(); @endphp
      @if($open > 0)<span class="badge">{{ $open }}</span>@endif
    </a>

    <div class="menu-section">Système</div>
    <a href="{{ route('admin.settings') }}" class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
      <i class="fas fa-cog"></i> Paramètres
    </a>
    <a href="{{ route('admin.logout') }}" class="menu-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">@csrf</form>
  </nav>

  <div class="sidebar-bottom">
    <div class="admin-info">
      <div class="admin-avatar">{{ strtoupper(substr(Auth::guard('admin')->user()->name ?? 'A', 0, 1)) }}</div>
      <div class="admin-info-text">
        <div class="admin-name">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</div>
        <div class="admin-role">Administrateur</div>
      </div>
    </div>
  </div>
</aside>

<!-- Main -->
<div class="main">
  <header class="topbar">
    <div class="topbar-title">@yield('title', 'Dashboard')</div>
    <div class="topbar-actions">
      <div class="topbar-btn"><i class="fas fa-search" style="font-size:14px"></i></div>
      <a href="{{ route('admin.support.index') }}" class="topbar-btn">
        <i class="fas fa-bell" style="font-size:14px"></i>
        <span class="notif-dot"></span>
      </a>
      <div class="admin-avatar" style="cursor:pointer">{{ strtoupper(substr(Auth::guard('admin')->user()->name ?? 'A', 0, 1)) }}</div>
    </div>
  </header>

  <div class="content">
    @if(session('success'))
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    @yield('content')
  </div>
</div>

<script>
// Auto-hide alerts
setTimeout(() => { document.querySelectorAll('.alert').forEach(a => a.style.display='none') }, 4000);
</script>
@yield('extra_js')
</body>
</html>
