<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($firebaseConfig = array_filter([
        'apiKey' => config('services.firebase.api_key'),
        'authDomain' => config('services.firebase.auth_domain'),
        'projectId' => config('services.firebase.project_id'),
        'storageBucket' => config('services.firebase.storage_bucket'),
        'messagingSenderId' => config('services.firebase.messaging_sender_id'),
        'appId' => config('services.firebase.app_id'),
        'vapidKey' => config('services.firebase.vapid_key'),
    ], fn ($value) => filled($value)))
    <script>
        window.__APP_FIREBASE_CONFIG__ = @json($firebaseConfig);
    </script>
    <title>@yield('title', 'appjeunesse-kzi') — La Parole Éternelle Kolwezi</title>
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
<body class="guest-page min-h-screen bg-slate-950 font-sans text-slate-100 antialiased {{ request()->routeIs('home') ? 'home-page' : '' }}">
    @php($appSettings = \App\Models\AppSetting::current())
    <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(168,85,247,0.18),transparent_28%)]"></div>
    <div id="app-toast-stack" class="pointer-events-none fixed right-4 top-4 z-50 flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-3"></div>

    <header class="relative z-10 border-b border-white/10 bg-slate-950/70 backdrop-blur-xl">
        <div class="guest-header-inner mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:flex-nowrap sm:py-4">
            <a href="{{ route('home') }}" class="guest-brand flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                <img src="{{ $appSettings->logo_url ?: asset('logoEglise.jpg') }}" class="floaty h-10 w-10 shrink-0 rounded-2xl object-cover object-center ring-2 ring-cyan-400/60 shadow-lg shadow-indigo-500/30 sm:h-11 sm:w-11" alt="Logo {{ $appSettings->church_name }}">
                <span>
                    <span class="block whitespace-nowrap text-xs font-bold leading-tight text-white text-glow sm:text-lg">{{ $appSettings->application_name }}</span>
                    <span class="hidden text-xs text-slate-300 sm:block">{{ $appSettings->church_name }}</span>
                </span>
            </a>
            <div class="guest-header-actions flex shrink-0 items-center gap-2">
                <button type="button" data-theme-toggle class="theme-toggle px-2 py-2 text-xs sm:px-3 sm:text-sm" aria-label="Activer le mode clair">
                    <span data-theme-icon aria-hidden="true">☀</span>
                    <span data-theme-label>Clair</span>
                </button>
                <button type="button" data-app-install class="app-install-button px-2.5 py-2 sm:px-3" aria-label="Installer l’application">
                    <span aria-hidden="true">＋</span>
                    <span>Installer l’app</span>
                </button>
                <nav class="flex shrink-0 items-center gap-1 text-xs sm:gap-2 sm:text-sm">
                    <a href="{{ route('home') }}" class="guest-home-link whitespace-nowrap rounded-xl border border-cyan-400/25 bg-cyan-400/10 px-2 py-2 font-semibold text-cyan-100 transition hover:border-cyan-300/50 hover:bg-cyan-400/20 hover:text-white sm:px-3" aria-label="Retourner à la page d’accueil">
                        <span aria-hidden="true">⌂</span>
                        <span>Accueil</span>
                    </a>
                    <a href="{{ route('videos.archive') }}" class="whitespace-nowrap rounded-xl px-2 py-2 text-slate-200 transition hover:bg-white/5 hover:text-white sm:px-3">Archives vidéos</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="whitespace-nowrap rounded-xl bg-indigo-500/20 px-3 py-2 font-semibold text-indigo-100 transition hover:bg-indigo-500/35 sm:px-4">Mon espace</a>
                    @else
                        <a href="{{ route('login') }}" class="whitespace-nowrap rounded-xl px-2 py-2 text-slate-200 transition hover:bg-white/5 hover:text-white sm:px-3">Connexion</a>
                        <a href="{{ route('register') }}" class="whitespace-nowrap rounded-xl bg-gradient-to-r from-amber-400 to-orange-400 px-3 py-2 font-semibold text-slate-950 shadow-lg shadow-amber-500/25 transition hover:brightness-110 sm:px-4">Inscription</a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main class="relative z-10 mx-auto max-w-6xl px-4 py-8">
        @if (session('success'))
            <div data-app-flash data-app-flash-title="Action réussie" data-app-flash-type="success" hidden>{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="relative z-10 mt-12 border-t border-white/10 bg-slate-950/80 py-6 text-center text-sm text-slate-400 backdrop-blur-xl">
        © {{ date('Y') }} Jeunesse La Parole Éternelle — Kolwezi · appjeunesse-kzi
    </footer>
</body>
</html>
