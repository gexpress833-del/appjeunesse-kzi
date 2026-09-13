@extends('layouts.guest')

@section('title', 'Bienvenue')

@section('content')

    {{-- ==================== CARROUSEL VITRINE ==================== --}}
    <section class="carousel-atmosphere relative overflow-hidden rounded-[2rem] border border-white/10 text-white shadow-[0_30px_80px_rgba(37,99,235,0.25)] ring-1 ring-white/10">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.18),transparent_25%),radial-gradient(circle_at_bottom_right,_rgba(56,189,248,0.18),transparent_25%)]"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 to-transparent"></div>
        <div class="absolute -left-12 top-10 h-40 w-40 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="absolute -right-12 bottom-8 h-48 w-48 rounded-full bg-violet-400/20 blur-3xl"></div>

        <div id="carousel" class="relative min-h-[320px] overflow-hidden sm:min-h-[380px]" aria-live="polite">
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
                <div class="carousel-slide absolute inset-0 flex flex-col items-center justify-center gap-4 px-8 py-12 text-center sm:px-14 {{ $i === 0 ? 'is-active' : 'pointer-events-none' }}" data-slide-index="{{ $i }}">
                    @if ($slide['kind'] === 'verset')
                        <span class="carousel-label">📖 {{ $slide['title'] }}</span>
                        <blockquote class="carousel-quote max-w-4xl">« {{ $slide['content'] }} »</blockquote>
                        @if ($slide['ref'])<p class="carousel-reference">{{ $slide['ref'] }}</p>@endif
                    @elseif ($slide['kind'] === 'temoignage')
                        <span class="carousel-label cream">💬 {{ $slide['title'] }}</span>
                        <blockquote class="carousel-quote max-w-4xl">« {{ $slide['content'] }} »</blockquote>
                        @if ($slide['ref'])<p class="carousel-reference">— {{ $slide['ref'] }}</p>@endif
                    @elseif ($slide['kind'] === 'banner')
                        <span class="carousel-label rose">📣 {{ $slide['title'] }}</span>
                        <p class="carousel-message max-w-3xl">{{ $slide['content'] }}</p>
                    @else
                        <span class="carousel-label sky">📅 {{ $slide['title'] }}</span>
                        <ul class="mx-auto flex w-full max-w-5xl flex-wrap justify-center gap-4">
                            @foreach ($slide['events'] as $event)
                                <li class="carousel-event-card flex min-h-36 w-full max-w-md flex-1 basis-full flex-col items-center justify-center gap-3 rounded-2xl border border-white/10 bg-white/8 p-4 text-center shadow-[0_20px_40px_rgba(15,23,42,0.20)] backdrop-blur-xl sm:basis-[calc(50%-0.5rem)] sm:flex-none sm:flex-row sm:gap-5">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-white">{{ $event->name }}</p>
                                        <p class="text-sm text-cyan-100/90">{{ $event->date->translatedFormat('l d F Y · H\hi') }}</p>
                                    </div>
                                    @if ($event->photo_url)
                                        <img src="{{ $event->photo_url }}" alt="Affiche de {{ $event->name }}" class="h-40 w-40 shrink-0 rounded-xl object-cover ring-2 ring-white/20 sm:h-40 sm:w-40">
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            @if (empty($slides))
                <div class="absolute inset-0 flex items-center justify-center">
                    <p class="text-lg text-sky-100/90">Bienvenue sur la plateforme de la jeunesse !</p>
                </div>
            @endif
        </div>

        @if (count($slides) > 1)
            <button onclick="window.carouselGo(window.carouselIndex - 1)" class="carousel-nav absolute left-3 top-1/2 -translate-y-1/2 rounded-full border border-white/15 bg-slate-950/20 px-3 py-2 text-xl text-white backdrop-blur-xl shadow-lg shadow-sky-950/30 transition hover:scale-105 hover:bg-slate-950/30">‹</button>
            <button onclick="window.carouselGo(window.carouselIndex + 1)" class="carousel-nav absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-white/15 bg-slate-950/20 px-3 py-2 text-xl text-white backdrop-blur-xl shadow-lg shadow-sky-950/30 transition hover:scale-105 hover:bg-slate-950/30">›</button>
            <div id="carousel-dots" class="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-2">
                @foreach ($slides as $i => $slide)
                    <button onclick="window.carouselGo({{ $i }})" data-dot="{{ $i }}" class="h-2.5 w-2.5 rounded-full border border-white/30 transition {{ $i === 0 ? 'bg-white shadow-[0_0_12px_rgba(255,255,255,0.9)]' : 'bg-white/35' }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    <p class="mx-auto mt-8 max-w-3xl text-center text-2xl font-bold text-slate-900 sm:text-3xl">
        Une jeunesse qui sert Dieu avec Excellence et Dévouement
    </p>

    <section class="youth-path mt-10" aria-labelledby="youth-path-title">
        <div class="youth-path-heading">
            <div>
                <p class="youth-path-kicker"><span></span> Notre fonctionnement</p>
                <h2 id="youth-path-title">Une jeunesse en mouvement</h2>
                <p>À La Parole Éternelle Kolwezi, chaque jeune avance dans la foi, trouve sa place et met ses talents au service de Dieu et de la communauté.</p>
            </div>
            <div class="youth-path-code" aria-hidden="true">LPK / JEUNESSE / 04</div>
        </div>

        <div class="youth-path-grid">
            <article class="youth-path-card">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">01</span>
                    <span class="youth-path-symbol" aria-hidden="true">✦</span>
                </div>
                <h3>Grandir dans la foi</h3>
                <p>La Parole, la prière et les temps de partage nous aident à construire une foi solide et vivante.</p>
                <span class="youth-path-label">Foi · Prière · Parole</span>
            </article>

            <article class="youth-path-card">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">02</span>
                    <span class="youth-path-symbol" aria-hidden="true">◌</span>
                </div>
                <h3>Vivre la communauté</h3>
                <p>Nous créons des liens, nous nous soutenons et nous avançons ensemble comme une famille.</p>
                <span class="youth-path-label">Accueil · Écoute · Unité</span>
            </article>

            <article class="youth-path-card">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">03</span>
                    <span class="youth-path-symbol" aria-hidden="true">⌁</span>
                </div>
                <h3>Servir avec ses dons</h3>
                <p>Chacun peut servir selon ses dons : communication, musique, technique, accueil, social, lecture biblique, intercession et protocole.</p>
                <span class="youth-path-label">DCC · Social · DLB · Intercession · Protocole</span>
            </article>

            <article class="youth-path-card youth-path-card-featured">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">04</span>
                    <span class="youth-path-symbol" aria-hidden="true">↗</span>
                </div>
                <h3>Participer à la mission</h3>
                <p>Retrouve les cultes, événements, directs et archives pour rester connecté à la vie de la jeunesse à Kolwezi.</p>
                <a href="{{ route('videos.archive') }}" class="youth-path-link">Explorer la médiathèque <span aria-hidden="true">→</span></a>
            </article>
        </div>
    </section>

    <script>
        window.carouselIndex = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('#carousel-dots [data-dot]');
        const carousel = document.getElementById('carousel');
        let carouselTimer;
        let dragStartX = 0;
        let dragDeltaX = 0;

        function updateSlidePositions() {
            if (!slides.length) return;

            slides.forEach((slide, index) => {
                const offset = (index - window.carouselIndex + slides.length) % slides.length;
                let x = '0%';
                let opacity = 1;
                let visible = true;

                if (offset === 0) {
                    x = '0%';
                    opacity = 1;
                    visible = true;
                } else if (offset < slides.length / 2) {
                    x = '110%';
                    opacity = 0.18;
                    visible = false;
                } else {
                    x = '-110%';
                    opacity = 0.18;
                    visible = false;
                }

                slide.style.transform = `translate3d(${x}, 0, 0) scale(${offset === 0 ? 1.015 : 0.96})`;
                slide.style.opacity = String(opacity);
                slide.style.visibility = visible ? 'visible' : 'hidden';
                slide.classList.toggle('is-active', index === window.carouselIndex);
                slide.classList.toggle('pointer-events-none', index !== window.carouselIndex);
            });

            dots.forEach((dot, index) => {
                dot.classList.toggle('bg-white', index === window.carouselIndex);
                dot.classList.toggle('shadow-[0_0_12px_rgba(255,255,255,0.9)]', index === window.carouselIndex);
                dot.classList.toggle('bg-white/35', index !== window.carouselIndex);
            });
        }

        window.carouselGo = function (index, restart = true) {
            if (!slides.length) return;
            window.carouselIndex = (index + slides.length) % slides.length;
            updateSlidePositions();

            if (restart) {
                window.carouselStart();
            }
        };

        window.carouselStart = function () {
            window.clearInterval(carouselTimer);
            carouselTimer = window.setInterval(() => window.carouselGo(window.carouselIndex + 1, false), 5000);
        };

        if (carousel && slides.length > 1) {
            carousel.addEventListener('pointerdown', (event) => {
                dragStartX = event.clientX;
                dragDeltaX = 0;
                carousel.setPointerCapture(event.pointerId);
            });

            carousel.addEventListener('pointermove', (event) => {
                if (dragStartX === 0) return;
                dragDeltaX = event.clientX - dragStartX;
            });

            carousel.addEventListener('pointerup', () => {
                if (Math.abs(dragDeltaX) > 80) {
                    window.carouselGo(window.carouselIndex + (dragDeltaX < 0 ? 1 : -1), true);
                }
                dragStartX = 0;
                dragDeltaX = 0;
            });

            carousel.addEventListener('pointerleave', () => {
                dragStartX = 0;
                dragDeltaX = 0;
            });

            carousel.addEventListener('mouseenter', () => window.clearInterval(carouselTimer));
            carousel.addEventListener('mouseleave', window.carouselStart);
            updateSlidePositions();
            window.carouselStart();
        }
    </script>

    {{-- ==================== LIVE VIDÉO ==================== --}}
    <section class="mt-10">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-2xl font-bold text-slate-900">
                Culte vidéo
                @if ($live && $live->is_active)
                    <span class="rounded-full px-2 py-0.5 text-xs font-bold uppercase text-white {{ $live->broadcast_type === 'replay' ? 'bg-slate-600' : 'animate-pulse bg-rose-600' }}">
                        {{ $live->broadcast_type === 'replay' ? 'Retransmission' : 'En direct' }}
                    </span>
                @endif
            </h2>
            @if ($live && $live->is_active)
                <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-cyan-700">Live social</span>
            @endif
        </div>

        @if ($live && $live->is_active && \App\Support\VideoEmbed::toEmbed($live->media_url))
            <div class="live-feed-shell">
                <div class="live-feed-grid">
                    <div class="video-feed-surface">
                        <div class="video-shell" data-video-player data-video-id="{{ \App\Support\VideoEmbed::youtubeId($live->media_url) }}">
                            <div class="aspect-video overflow-hidden bg-black">
                                <iframe src="{{ \App\Support\VideoEmbed::toEmbed($live->media_url) }}"
                                        class="h-full w-full" style="border:0" tabindex="-1"
                                        allow="autoplay; encrypted-media; picture-in-picture"
                                        title="{{ $live->title }}"></iframe>
                            </div>
                            @if (\App\Support\VideoEmbed::youtubeId($live->media_url))
                                <button type="button" class="video-play-button" data-video-toggle aria-label="Lire la vidéo">▶</button>
                            @endif
                        </div>
                    </div>

                    <aside class="live-feed-sidebar">
                        <div class="live-feed-card live-feed-stats">
                            <div class="live-feed-stat-title">Engagement</div>
                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-200">
                                @auth
                                    <form method="POST" action="{{ route('videos.like', $liveArchive) }}" data-video-like-form data-video-id="{{ $liveArchive->id }}" class="inline-block">
                                        @csrf
                                        <button type="submit" data-like-button data-like-count-target="{{ $liveArchive->id }}" class="live-social-pill live-social-pill-like {{ $liveArchive->likes()->where('user_id', auth()->id())->exists() ? 'is-liked' : '' }}" aria-pressed="{{ $liveArchive->likes()->where('user_id', auth()->id())->exists() ? 'true' : 'false' }}">
                                            <span aria-hidden="true">♥</span>
                                            <span data-like-count="{{ $liveArchive->id }}">{{ $liveArchive->likes_count }}</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="live-social-pill live-social-pill-like"><span aria-hidden="true">♥</span> {{ $liveArchive->likes_count }}</span>
                                @endauth
                                <span class="live-social-pill"><span aria-hidden="true">💬</span> <span data-comments-count="{{ $liveArchive->id }}">{{ $liveArchive->comments->count() }}</span></span>
                                <span class="live-social-pill"><span aria-hidden="true">◉</span> {{ $liveArchive->views_count }}</span>
                            </div>
                        </div>

                        <div class="live-feed-card live-feed-comments">
                            <div class="live-feed-comment-header">
                                <span>Commentaires</span>
                                <span class="live-feed-dot"></span>
                            </div>

                            @if ($liveArchive->comments->isNotEmpty())
                                <div class="comment-stream" data-comments-list="{{ $liveArchive->id }}">
                                    @foreach ($liveArchive->comments->take(4) as $comment)
                                        <div class="comment-bubble">
                                            <span class="comment-user">{{ $comment->user->full_name }}</span>
                                            <p>{{ $comment->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="comment-stream empty" data-comments-list="{{ $liveArchive->id }}">
                                    <div class="comment-bubble muted">
                                        <span class="comment-user">La communauté</span>
                                        <p>Soyez le premier à commenter ce live.</p>
                                    </div>
                                </div>
                            @endif

                            @auth
                                <form method="POST" action="{{ route('videos.comment', $liveArchive) }}" data-video-comment-form data-video-id="{{ $liveArchive->id }}" class="comment-form mt-4">
                                    @csrf
                                    <input name="body" required maxlength="1000" placeholder="Écrire un commentaire..." class="comment-input">
                                    <button type="submit" class="comment-submit">Publier</button>
                                </form>
                            @endauth
                        </div>
                    </aside>
                </div>

                @if ($live->content)
                    <p class="mt-4 text-sm font-medium text-slate-700">{{ $live->content }}</p>
                @endif
            </div>
        @else
            <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
                <p class="text-4xl">📺</p>
                <p class="mt-2 font-medium">Aucun direct pour le moment</p>
                <p class="text-sm">Le culte de la jeunesse est retransmis en direct chaque samedi à partir de 16h30.</p>
            </div>
        @endif
    </section>

    @if ($videoArchives->isNotEmpty())
        <section class="mt-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-600">Médiathèque</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">Archives vidéo</h2>
                </div>
                <p class="text-sm text-slate-500">Retrouvez les directs et retransmissions précédents.</p>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                @foreach ($videoArchives as $video)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="video-shell aspect-video bg-slate-950" data-video-player data-video-id="{{ \App\Support\VideoEmbed::youtubeId($video->media_url) }}">
                            <iframe src="{{ \App\Support\VideoEmbed::toEmbed($video->media_url) }}" class="h-full w-full" style="border:0" loading="lazy" tabindex="-1" allow="autoplay; encrypted-media; picture-in-picture" title="{{ $video->title }}"></iframe>
                            @if (\App\Support\VideoEmbed::youtubeId($video->media_url))
                                <button type="button" class="video-play-button" data-video-toggle aria-label="Lire la vidéo">▶</button>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wide text-cyan-700">{{ $video->broadcast_type === 'replay' ? 'Retransmission' : 'En direct' }}</span>
                                    <h3 class="mt-1 font-bold text-slate-900">{{ $video->title }}</h3>
                                </div>
                                <span class="text-xs text-slate-500">{{ $video->created_at->diffForHumans() }}</span>
                            </div>
                            @if ($video->description)<p class="mt-2 text-sm text-slate-600">{{ $video->description }}</p>@endif
                            <div class="mt-4 flex items-center gap-3">
                                @auth
                                    <form method="POST" action="{{ route('videos.like', $video) }}">
                                        @csrf
                                        <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">♥ {{ $video->likes_count }}</button>
                                    </form>
                                @else
                                    <span class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-500">♥ {{ $video->likes_count }}</span>
                                @endauth
                                <span class="text-sm text-slate-500">💬 {{ $video->comments->count() }} commentaire(s)</span>
                                <span class="text-sm text-slate-500">◉ {{ $video->views_count }} vue(s)</span>
                            </div>
                            @auth
                                <form method="POST" action="{{ route('videos.comment', $video) }}" class="mt-4 flex gap-2">
                                    @csrf
                                    <input name="body" required maxlength="1000" placeholder="Écrire un commentaire..." class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm">
                                    <button class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Publier</button>
                                </form>
                            @endauth
                            @if ($video->comments->isNotEmpty())
                                <div class="mt-4 space-y-2 border-t border-slate-100 pt-3">
                                    @foreach ($video->comments->take(3) as $comment)
                                        <p class="text-sm text-slate-600"><strong class="text-slate-900">{{ $comment->user->full_name }}</strong> {{ $comment->body }}</p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <script src="https://www.youtube.com/iframe_api"></script>
    <script>
        const videoPlayers = new Map();
        let youtubeApiReady = false;

        function setVideoToggleState(button, state) {
            if (!button) return;
            const shell = button.closest('[data-video-player]');
            if (shell) {
                shell.classList.toggle('is-playing', state === 'playing');
            }
            button.textContent = state === 'playing' ? '❚❚' : '▶';
            button.setAttribute('aria-label', state === 'playing' ? 'Pause la vidéo' : 'Lire la vidéo');
        }

        function updateLikeButton(form, liked, count) {
            const button = form.querySelector('[data-like-button]');
            const countNode = form.querySelector('[data-like-count]');

            if (!button || !countNode) return;

            countNode.textContent = count;
            button.classList.toggle('is-liked', liked);
            button.setAttribute('aria-pressed', liked ? 'true' : 'false');
        }

        function updateCommentList(videoId, comment, count) {
            const commentCountNode = document.querySelector('[data-comments-count="' + videoId + '"]');
            const listNode = document.querySelector('[data-comments-list="' + videoId + '"]');

            if (commentCountNode) commentCountNode.textContent = count;
            if (!listNode) return;

            listNode.classList.remove('hidden');
            listNode.insertAdjacentHTML('afterend', '');

            const item = document.createElement('p');
            item.className = 'text-sm text-slate-200';
            item.innerHTML = '<strong class="font-semibold text-white">' + comment.user + '</strong> ' + comment.body;
            listNode.prepend(item);
        }

        document.querySelectorAll('[data-video-like-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (form.dataset.busy === 'true') return;

                form.dataset.busy = 'true';
                const token = form.querySelector('input[name=_token]')?.value || '';
                const button = form.querySelector('[data-like-button]');
                if (button) button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                        },
                        body: new FormData(form),
                    });

                    if (!response.ok) throw new Error('Like failed');

                    const data = await response.json();
                    updateLikeButton(form, data.liked, data.count);
                } catch (error) {
                    console.error(error);
                } finally {
                    form.dataset.busy = 'false';
                    if (button) button.disabled = false;
                }
            });
        });

        document.querySelectorAll('[data-video-comment-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (form.dataset.busy === 'true') return;

                const input = form.querySelector('input[name="body"]');
                if (!input || !input.value.trim()) return;

                form.dataset.busy = 'true';
                const token = form.querySelector('input[name=_token]')?.value || '';
                const button = form.querySelector('button[type="submit"]');
                if (button) button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                        },
                        body: new FormData(form),
                    });

                    if (!response.ok) throw new Error('Comment failed');

                    const data = await response.json();
                    updateCommentList(form.dataset.videoId, data.comment, data.count);
                    input.value = '';
                } catch (error) {
                    console.error(error);
                } finally {
                    form.dataset.busy = 'false';
                    if (button) button.disabled = false;
                }
            });
        });

        window.onYouTubeIframeAPIReady = function () {
            youtubeApiReady = true;
            document.querySelectorAll('[data-video-player][data-video-id]').forEach((shell) => {
                const iframe = shell.querySelector('iframe');
                if (!iframe || !window.YT || !window.YT.Player) return;

                const player = new YT.Player(iframe, {
                    events: {
                        onReady: () => {
                            videoPlayers.set(shell, player);
                            const button = shell.querySelector('[data-video-toggle]');
                            if (button) setVideoToggleState(button, 'paused');
                        },
                        onStateChange: (event) => {
                            const button = shell.querySelector('[data-video-toggle]');
                            if (button) {
                                setVideoToggleState(button, event.data === YT.PlayerState.PLAYING ? 'playing' : 'paused');
                            }
                        },
                    },
                });
            });
        };

        document.querySelectorAll('[data-video-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const shell = button.closest('[data-video-player]');
                const player = shell ? videoPlayers.get(shell) : null;

                if (!youtubeApiReady || !player || typeof player.playVideo !== 'function' || typeof player.pauseVideo !== 'function') {
                    return;
                }

                const currentState = player.getPlayerState && player.getPlayerState();
                const isPlaying = currentState === YT.PlayerState.PLAYING;

                if (isPlaying) {
                    player.pauseVideo();
                    setVideoToggleState(button, 'paused');
                } else {
                    player.playVideo();
                    setVideoToggleState(button, 'playing');
                }
            });
        });
    </script>

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
            <p class="mx-auto mt-2 max-w-xl text-slate-300">Créez votre compte pour suivre vos présences, consulter l'annuaire, la galerie et les événements. Votre compte sera validé par le Président responsable de la jeunesse de La Parole Éternelle, Centre-Ville de Kolwezi.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-xl bg-amber-400 px-6 py-3 font-semibold text-amber-950 hover:bg-amber-300">Créer mon compte</a>
                <a href="{{ route('login') }}" class="rounded-xl border border-slate-600 px-6 py-3 font-semibold hover:bg-slate-800">Se connecter</a>
            </div>
        </section>
    @endguest

@endsection
