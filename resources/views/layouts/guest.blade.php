<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'appjeunesse-kzi') — La Parole Éternelle Kolwezi</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logoEglise.jpg') }}">
    <script>
        document.documentElement.dataset.theme = localStorage.getItem('appjeunesse-theme') || 'dark';
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(168,85,247,0.18),transparent_28%)]"></div>

    <header class="relative z-10 border-b border-white/10 bg-slate-950/70 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:flex-nowrap sm:py-4">
            <a href="{{ route('home') }}" class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                <img src="{{ asset('logoEglise.jpg') }}" class="floaty h-10 w-10 shrink-0 rounded-2xl object-cover object-center ring-2 ring-cyan-400/60 shadow-lg shadow-indigo-500/30 sm:h-11 sm:w-11" alt="Logo La Parole Éternelle Kolwezi">
                <span>
                    <span class="block whitespace-nowrap text-xs font-bold leading-tight text-white text-glow sm:text-lg">appjeunesse-kzi</span>
                    <span class="hidden text-xs text-slate-300 sm:block">La Parole Éternelle — Kolwezi</span>
                </span>
            </a>
            <button type="button" data-theme-toggle class="theme-toggle shrink-0 px-2 py-2 text-xs sm:px-3 sm:text-sm" aria-label="Activer le mode clair">
                <span data-theme-icon aria-hidden="true">☀</span>
                <span data-theme-label class="hidden sm:inline">Clair</span>
            </button>
            <nav class="flex shrink-0 items-center gap-1 text-xs sm:gap-2 sm:text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="whitespace-nowrap rounded-xl bg-indigo-500/20 px-3 py-2 font-semibold text-indigo-100 transition hover:bg-indigo-500/35 sm:px-4">Mon espace</a>
                @else
                    <a href="{{ route('login') }}" class="whitespace-nowrap rounded-xl px-2 py-2 text-slate-200 transition hover:bg-white/5 hover:text-white sm:px-3">Connexion</a>
                    <a href="{{ route('register') }}" class="whitespace-nowrap rounded-xl bg-gradient-to-r from-amber-400 to-orange-400 px-3 py-2 font-semibold text-slate-950 shadow-lg shadow-amber-500/25 transition hover:brightness-110 sm:px-4">Inscription</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="relative z-10 mx-auto max-w-6xl px-4 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 shadow-lg shadow-emerald-500/10">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="relative z-10 mt-12 border-t border-white/10 bg-slate-950/80 py-6 text-center text-sm text-slate-400 backdrop-blur-xl">
        © {{ date('Y') }} Jeunesse La Parole Éternelle — Kolwezi · appjeunesse-kzi
    </footer>
</body>
</html>
