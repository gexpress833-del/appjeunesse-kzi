@extends('layouts.guest')

@section('title', 'Bienvenue')

@section('content')

    {{-- ==================== CARROUSEL VITRINE ==================== --}}
    <section class="carousel-atmosphere relative overflow-hidden rounded-3xl text-white shadow-xl shadow-indigo-950/30">
        <div id="carousel" class="relative min-h-[320px] sm:min-h-[380px]">
            @php
                $slides = [];
                foreach ($versets as $v) {
                    $slides[] = ['kind' => 'verset', 'title' => $v->title ?? 'Le Verset du Jour', 'content' => $v->content, 'ref' => $v->author_or_reference];
                }
                foreach ($temoignages as $t) {
                    $slides[] = ['kind' => 'temoignage', 'title' => $t->title ?? 'Témoignage', 'content' => $t->content, 'ref' => $t->author_or_reference];
                }
                foreach ($banners as $b) {
                    $slides[] = ['kind' => 'banner', 'title' => $b->title, 'content' => $b->content, 'ref' => $b->author_or_reference, 'media' => $b->media_url];
                }
                if ($upcomingEvents->isNotEmpty()) {
                    $slides[] = ['kind' => 'events', 'title' => 'Événements à venir', 'events' => $upcomingEvents];
                }
            @endphp

            @foreach ($slides as $i => $slide)
                <div class="carousel-slide absolute inset-0 flex flex-col items-center justify-center gap-4 px-8 py-12 text-center sm:px-14 {{ $i === 0 ? 'is-active' : 'pointer-events-none' }}" style="transition: opacity 350ms ease-out, visibility 350ms ease-out;">
                    @if ($slide['kind'] === 'verset')
                        <span class="carousel-label rounded-full bg-amber-400/90 px-4 py-1.5 uppercase text-amber-950">📖 {{ $slide['title'] }}</span>
                        <blockquote class="carousel-quote max-w-3xl">« {{ $slide['content'] }} »</blockquote>
                        @if ($slide['ref'])<p class="carousel-reference">{{ $slide['ref'] }}</p>@endif
                    @elseif ($slide['kind'] === 'temoignage')
                        <span class="carousel-label rounded-full bg-emerald-400/90 px-4 py-1.5 uppercase text-emerald-950">💬 {{ $slide['title'] }}</span>
                        <blockquote class="carousel-quote max-w-3xl">« {{ $slide['content'] }} »</blockquote>
                        @if ($slide['ref'])<p class="carousel-reference">— {{ $slide['ref'] }}</p>@endif
                    @elseif ($slide['kind'] === 'banner')
                        <span class="carousel-label rounded-full bg-rose-400/90 px-4 py-1.5 uppercase text-rose-950">📣 {{ $slide['title'] }}</span>
                        <p class="carousel-message max-w-3xl">{{ $slide['content'] }}</p>
                    @else
                        <span class="carousel-label rounded-full bg-sky-400/90 px-4 py-1.5 uppercase text-sky-950">📅 {{ $slide['title'] }}</span>
                        <ul class="mx-auto flex w-full max-w-5xl flex-wrap justify-center gap-4">
                            @foreach ($slide['events'] as $event)
                                <li class="flex min-h-36 w-full max-w-md flex-1 basis-full flex-col items-center justify-center gap-3 rounded-xl bg-white/10 p-4 text-center backdrop-blur sm:basis-[calc(50%-0.5rem)] sm:flex-none sm:flex-row sm:gap-5">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold">{{ $event->name }}</p>
                                        <p class="text-sm text-indigo-200">{{ $event->date->translatedFormat('l d F Y · H\hi') }}</p>
                                    </div>
                                    @if ($event->photo_url)
                                        <img src="{{ $event->photo_url }}" alt="Affiche de {{ $event->name }}" class="h-40 w-40 shrink-0 rounded-lg object-cover sm:h-40 sm:w-40">
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            @if (empty($slides))
                <div class="absolute inset-0 flex items-center justify-center">
                    <p class="text-lg text-indigo-200">Bienvenue sur la plateforme de la jeunesse !</p>
                </div>
            @endif
        </div>

        @if (count($slides) > 1)
            <button onclick="window.carouselGo(window.carouselIndex - 1)" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/15 px-3 py-2 text-xl backdrop-blur hover:bg-white/25">‹</button>
            <button onclick="window.carouselGo(window.carouselIndex + 1)" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/15 px-3 py-2 text-xl backdrop-blur hover:bg-white/25">›</button>
            <div id="carousel-dots" class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                @foreach ($slides as $i => $slide)
                    <button onclick="window.carouselGo({{ $i }})" data-dot="{{ $i }}" class="h-2.5 w-2.5 rounded-full {{ $i === 0 ? 'bg-white' : 'bg-white/40' }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    <p class="mx-auto mt-8 max-w-3xl text-center text-2xl font-bold text-slate-900 sm:text-3xl">
        Une jeunesse qui sert Dieu avec Excellence et Dévouement
    </p>

    <script>
        // Carrousel : rotation automatique toutes les 5 secondes avec pause au survol.
        window.carouselIndex = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('#carousel-dots [data-dot]');
        const carousel = document.getElementById('carousel');
        let carouselTimer;

        window.carouselGo = function (index, restart = true) {
            if (!slides.length) return;
            window.carouselIndex = (index + slides.length) % slides.length;
            slides.forEach((s, i) => {
                s.classList.toggle('is-active', i === window.carouselIndex);
                s.classList.toggle('pointer-events-none', i !== window.carouselIndex);
            });
            dots.forEach((d, i) => {
                d.classList.toggle('bg-white', i === window.carouselIndex);
                d.classList.toggle('bg-white/40', i !== window.carouselIndex);
            });

            if (restart) {
                window.carouselStart();
            }
        };

        window.carouselStart = function () {
            window.clearInterval(carouselTimer);
            carouselTimer = window.setInterval(() => window.carouselGo(window.carouselIndex + 1, false), 5000);
        };

        if (carousel && slides.length > 1) {
            carousel.addEventListener('mouseenter', () => window.clearInterval(carouselTimer));
            carousel.addEventListener('mouseleave', window.carouselStart);
            window.carouselStart();
        }
    </script>

    {{-- ==================== LIVE VIDÉO ==================== --}}
    <section class="mt-10">
        <h2 class="mb-4 flex items-center gap-2 text-2xl font-bold text-slate-900">
            🔴 Culte en direct
            @if ($live && $live->is_active)
                <span class="animate-pulse rounded-full bg-rose-600 px-2 py-0.5 text-xs font-bold uppercase text-white">Live</span>
            @endif
        </h2>

        @if ($live && $live->is_active && \App\Support\VideoEmbed::toEmbed($live->media_url))
            <div class="overflow-hidden rounded-2xl bg-black shadow-lg">
                <div class="aspect-video">
                    <iframe src="{{ \App\Support\VideoEmbed::toEmbed($live->media_url) }}"
                            class="h-full w-full" style="border:0"
                            allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen
                            title="{{ $live->title }}"></iframe>
                </div>
            </div>
            @if ($live->content)
                <p class="mt-3 text-slate-600">{{ $live->content }}</p>
            @endif
        @else
            <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
                <p class="text-4xl">📺</p>
                <p class="mt-2 font-medium">Aucun direct pour le moment</p>
                <p class="text-sm">Le culte de la jeunesse est retransmis en direct chaque samedi à partir de 16h30.</p>
            </div>
        @endif
    </section>

    {{-- ==================== ÉVÉNEMENTS À VENIR ==================== --}}
    @if ($upcomingEvents->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">📅 Événements à venir</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($upcomingEvents as $event)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @if ($event->photo_url)
                            <img src="{{ $event->photo_url }}" alt="Affiche de {{ $event->name }}" class="h-48 w-full object-cover">
                        @endif
                        <div class="p-5">
                            <p class="text-sm font-bold text-indigo-600">{{ $event->date->translatedFormat('l d F Y') }}</p>
                            <p class="text-sm text-slate-500">{{ $event->date->format('H\hi') }}</p>
                            <h3 class="mt-2 font-semibold text-slate-900">{{ $event->name }}</h3>
                            @if ($event->description)
                                <p class="mt-1 line-clamp-3 text-sm text-slate-600">{{ $event->description }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ==================== APPEL À L'ACTION ==================== --}}
    @guest
        <section class="mt-12 rounded-3xl bg-slate-900 px-6 py-12 text-center text-white">
            <h2 class="text-2xl font-bold">Vous faites partie de la jeunesse ?</h2>
            <p class="mx-auto mt-2 max-w-xl text-slate-300">Créez votre compte pour suivre vos présences, consulter l'annuaire, la galerie et les événements. Votre compte sera validé par l'administrateur.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-xl bg-amber-400 px-6 py-3 font-semibold text-amber-950 hover:bg-amber-300">Créer mon compte</a>
                <a href="{{ route('login') }}" class="rounded-xl border border-slate-600 px-6 py-3 font-semibold hover:bg-slate-800">Se connecter</a>
            </div>
        </section>
    @endguest

@endsection
