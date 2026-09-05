<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Espace membre') — appjeunesse-kzi</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logoEglise.jpg') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#07111f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ asset('logoEglise.jpg') }}">
    <script>
        document.documentElement.dataset.theme = localStorage.getItem('appjeunesse-theme') || 'dark';
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
<div class="relative flex min-h-screen overflow-x-hidden">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.22),transparent_25%),radial-gradient(circle_at_bottom_right,_rgba(34,211,238,0.18),transparent_35%)]"></div>
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-950/70 backdrop-blur-sm lg:hidden" onclick="window.closeSidebar()"></div>

    {{-- Barre latérale --}}
    <aside id="sidebar" class="sidebar-closed fixed inset-y-0 left-0 z-40 flex min-h-0 w-64 flex-col overflow-y-auto border-r border-white/10 bg-slate-950/80 text-slate-300 shadow-2xl shadow-indigo-950/20 backdrop-blur-xl transition-transform lg:static lg:flex lg:transform-none">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 px-5 py-5">
            <div class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('logoEglise.jpg') }}" class="h-10 w-10 rounded-2xl object-cover object-center ring-2 ring-cyan-400/60 pulse-glow" alt="Logo La Parole Éternelle Kolwezi">
            <div>
                <p class="text-sm font-bold text-white text-glow">appjeunesse-kzi</p>
                <p class="text-[11px] text-slate-400">La Parole Éternelle Kolwezi</p>
            </div>
            </div>
            <button type="button" class="rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-lg leading-none text-slate-200 lg:hidden" aria-label="Fermer le menu" onclick="window.closeSidebar()">×</button>
        </div>

        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
            @php($u = auth()->user())
            @php($unreadNotificationsCount = $u->unreadNotifications()->count())
            @foreach ([
                ['home', 'Accueil public', '🌐'],
                ['dashboard', 'Tableau de bord', '🏠'],
                ['profile.edit', 'Mon profil', '👤'],
                ['members.index', 'Annuaire', '👥'],
                ['events.index', 'Événements', '📅'],
                ['gallery.index', 'Galerie photos', '🖼️'],
                ['social-visits.index', 'Visites sociales', '🤝'],
            ] as [$route, $label, $icon])
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all {{ request()->routeIs($route) ? 'bg-gradient-to-r from-indigo-600/80 to-cyan-500/70 font-semibold text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <span>{{ $icon }}</span> {{ $label }}
                </a>
            @endforeach

            <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Mon activité</p>
            <a href="{{ route('dashboard.bilan') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all {{ request()->routeIs('dashboard.bilan') ? 'bg-gradient-to-r from-indigo-600/80 to-cyan-500/70 font-semibold text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"><span>📈</span> Ma progression</a>

            @if ($u->isAdmin() || $u->isSecretariat() || $u->isResponsable())
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Gestion</p>
                @if ($u->isAdmin() || $u->isSecretariat())
                    <a href="{{ route('members.create') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300 transition-all hover:bg-white/5 hover:text-white"><span>➕</span> Nouveau membre</a>
                @endif
                <a href="{{ route('attendances.pick') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all {{ request()->routeIs('attendances.*') ? 'bg-gradient-to-r from-indigo-600/80 to-cyan-500/70 font-semibold text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"><span>✅</span> Présences</a>
                @if ($unreadNotificationsCount > 0)
                    <a href="{{ route('notifications.index') }}" class="flex items-center justify-between gap-3 rounded-xl border border-amber-400/30 bg-amber-500/10 px-3 py-2.5 text-amber-100 transition-all hover:bg-amber-500/20">
                        <span class="flex items-center gap-3"><span>🔔</span> Notifications</span>
                        <span class="rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-slate-950">{{ $unreadNotificationsCount }}</span>
                    </a>
                @endif
                @if ($u->isAdmin() || $u->isSecretariat() || $u->isSocialResponsable())
                    <a href="{{ route('social-visits.create') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300 transition-all hover:bg-white/5 hover:text-white"><span>➕</span> Planifier une visite</a>
                @endif
            @endif

            @if ($u->managesMedia())
                <a href="{{ route('gallery.upload') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300 transition-all hover:bg-white/5 hover:text-white"><span>📤</span> Publier des photos</a>
                <a href="{{ route('live.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all {{ request()->routeIs('live.*') ? 'bg-gradient-to-r from-indigo-600/80 to-cyan-500/70 font-semibold text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"><span>🔴</span> Direct vidéo</a>
            @endif

            @if ($u->isAdmin() || $u->isSecretariat())
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Communication</p>
                <a href="{{ route('carousel.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all {{ request()->routeIs('carousel.*') ? 'bg-gradient-to-r from-indigo-600/80 to-cyan-500/70 font-semibold text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"><span>🎡</span> Carrousel d'accueil</a>
                <a href="{{ route('attendances.report') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all {{ request()->routeIs('attendances.report') || request()->routeIs('attendances.export') ? 'bg-gradient-to-r from-indigo-600/80 to-cyan-500/70 font-semibold text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"><span>📊</span> Rapports</a>
            @endif

                @if ($u->isResponsable())
                    <a href="{{ route('attendances.pdf') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300 transition-all hover:bg-white/5 hover:text-white"><span>📄</span> Exporter mes rapports PDF</a>
                @endif

            @if ($u->isSecretariat() || $u->isAdmin())
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Comptes</p>
                <a href="{{ route('users.create') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-slate-300 transition-all hover:bg-white/5 hover:text-white"><span>👤</span> Créer un compte</a>
                @if ($u->isAdmin())
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all {{ request()->routeIs('users.index') ? 'bg-gradient-to-r from-indigo-600/80 to-cyan-500/70 font-semibold text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"><span>🔑</span> Utilisateurs</a>
                @endif
            @endif
        </nav>

        <div class="border-t border-white/10 px-4 py-4">
            <button type="button" data-theme-toggle class="theme-toggle mb-3 w-full" aria-label="Activer le mode clair">
                <span data-theme-icon aria-hidden="true">☀</span>
                <span data-theme-label>Clair</span>
            </button>
            <a href="{{ route('profile.edit') }}" class="mb-2 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-2 py-2.5 transition-all hover:bg-white/10">
                @if ($u->profile_photo_url)
                    <img src="{{ $u->profile_photo_url }}" class="h-9 w-9 rounded-full object-cover ring-2 ring-indigo-400/60" alt="">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-cyan-400 text-sm font-bold text-white">{{ strtoupper(substr($u->full_name, 0, 1)) }}</span>
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-white">{{ $u->full_name }}</span>
                    <span class="block text-[11px] uppercase text-slate-400">{{ $u->role }}@if($u->dept) · {{ $u->dept }}@endif</span>
                </span>
            </a>
            <button type="button" data-app-install hidden class="app-install-button mb-2 w-full"><span aria-hidden="true">＋</span> Installer l’application</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-left text-sm text-rose-200 transition-all hover:bg-rose-500/20">⏻ Déconnexion</button>
            </form>
        </div>
    </aside>

    {{-- Contenu --}}
    <div class="relative z-10 flex min-w-0 flex-1 flex-col">
        <header class="flex items-center gap-3 border-b border-white/10 bg-slate-950/70 px-4 py-3 backdrop-blur-xl lg:hidden">
            <button onclick="window.toggleSidebar()" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-white" aria-label="Ouvrir le menu">☰</button>
            <img src="{{ asset('logoEglise.jpg') }}" class="h-8 w-8 rounded-xl object-cover object-center ring-1 ring-cyan-400/60" alt="Logo La Parole Éternelle Kolwezi">
            <span class="min-w-0 flex-1 truncate font-bold text-white">appjeunesse-kzi</span>
            <button type="button" data-app-install hidden class="app-install-button px-2.5 py-2" aria-label="Installer l’application"><span aria-hidden="true">＋</span><span class="hidden sm:inline">Installer</span></button>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 shadow-lg shadow-emerald-500/10">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200 shadow-lg shadow-rose-500/10">
                    <p class="font-semibold">Veuillez corriger les erreurs :</p>
                    <ul class="mt-1 list-inside list-disc text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
