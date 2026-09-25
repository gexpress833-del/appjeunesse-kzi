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
                    $slides[] = ['kind' => 'verset', 'title' => $v->title ?? 'Le Verset du Jour', 'content' => $v->content, 'ref' => $v->author_or_reference, 'source' => $v->sourceLabel()];
                }
                foreach ($temoignages as $t) {
                    $slides[] = ['kind' => 'temoignage', 'title' => $t->title ?? 'Témoignage', 'content' => $t->content, 'ref' => $t->author_or_reference, 'source' => $t->sourceLabel()];
                }
                foreach ($banners as $b) {
                    $slides[] = ['kind' => 'banner', 'title' => $b->title, 'content' => $b->content, 'ref' => $b->author_or_reference, 'media' => $b->media_url, 'source' => $b->sourceLabel()];
                }
                foreach ($upcomingEvents as $event) {
                    $slides[] = [
                        'kind' => 'event',
                        'title' => $event->name,
                        'event' => $event,
                    ];
                }
            @endphp

                @foreach ($slides as $i => $slide)
                <div class="carousel-slide absolute inset-0 flex flex-col items-center justify-center gap-4 px-8 py-12 text-center sm:px-14 {{ $i === 0 ? 'is-active' : 'pointer-events-none' }}" data-slide-index="{{ $i }}">
                    <span class="rounded-full border border-white/30 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white">Source : {{ $slide['source'] ?? 'Église' }}</span>
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
                        @php($event = $slide['event'])
                        <span class="carousel-label sky">📅 {{ $slide['title'] }}</span>
                        <div class="carousel-event-card flex min-h-44 w-full max-w-3xl flex-col items-center justify-center gap-5 rounded-2xl border border-white/10 bg-white/8 p-5 text-center shadow-[0_20px_40px_rgba(15,23,42,0.20)] backdrop-blur-xl sm:flex-row sm:text-left">
                            @if ($event->photo_url)
                                <img src="{{ $event->photo_url }}" alt="Affiche de {{ $event->name }}" class="h-40 w-40 shrink-0 rounded-xl bg-slate-950/70 object-contain p-1 ring-2 ring-white/20" onerror="this.onerror=null; this.src='{{ asset('logoEglise.jpg') }}';">
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-2xl font-bold text-white">{{ $event->name }}</p>
                                <p class="mt-2 text-sm text-cyan-100/90">{{ $event->date->translatedFormat('l d F Y · H\hi') }}</p>
                                @if ($event->description)
                                    <p class="mt-3 text-sm leading-6 text-slate-200">{{ $event->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if (empty($slides))
                <div class="carousel-empty absolute inset-0 flex items-center justify-center px-6 text-center">
                    <p class="max-w-full text-lg text-sky-100/90">Bienvenue sur la plateforme de la jeunesse !</p>
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

    {{-- ==================== PORTAILS ==================== --}}
    <section class="mt-10">
        <div class="mb-6 flex flex-col gap-3 text-center md:text-left">
            <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-indigo-600">Choisissez votre espace</p>
            <h2 class="text-3xl font-black text-slate-900">Choisissez votre espace</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="group relative overflow-hidden rounded-[2rem] border border-[#D4A72C]/80 bg-slate-950/90 p-6 text-white shadow-[0_30px_90px_rgba(212,167,44,0.22)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(234,179,8,0.18),transparent_18%),radial-gradient(circle_at_bottom_right,_rgba(120,113,108,0.24),transparent_28%)]"></div>
                <div class="absolute inset-x-4 top-0 h-px bg-[#D4A72C]"></div>
                <div class="relative">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <span class="rounded-full border border-[#D4A72C]/80 bg-[#D4A72C]/15 px-3 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-[#F6E7A8]">PORTAIL ÉGLISE</span>
                        <span aria-hidden="true" class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#D4A72C]/70 bg-slate-900/70 text-2xl shadow-[0_0_16px_rgba(212,167,44,0.28)]">🏛️</span>
                    </div>
                    <h3 class="text-3xl font-black tracking-tight text-white">Portail Église</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-200">
                        Cultes, membres, événements et administration.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#F6E7A8]">
                        <span class="rounded-full border border-[#D4A72C]/70 bg-[#D4A72C]/10 px-2.5 py-1.5">Membres</span>
                        <span class="rounded-full border border-[#D4A72C]/70 bg-[#D4A72C]/10 px-2.5 py-1.5">Cultes</span>
                        <span class="rounded-full border border-[#D4A72C]/70 bg-[#D4A72C]/10 px-2.5 py-1.5">Événements</span>
                    </div>
                    <div class="mt-6">
                        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex rounded-xl border border-[#D4A72C]/80 bg-[#D4A72C]/15 px-4 py-2.5 font-semibold text-[#F8F1D5] shadow-[0_0_16px_rgba(212,167,44,0.24)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#D4A72C]/25">Accéder</a>
                    </div>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] border border-[#8B5CF6]/80 bg-slate-950/90 p-6 text-white shadow-[0_30px_90px_rgba(139,92,246,0.22)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(167,139,250,0.18),transparent_18%),radial-gradient(circle_at_bottom_right,_rgba(91,33,182,0.26),transparent_28%)]"></div>
                <div class="absolute inset-x-4 top-0 h-px bg-[#8B5CF6]"></div>
                <div class="relative">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <span class="rounded-full border border-[#8B5CF6]/80 bg-[#8B5CF6]/15 px-3 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-[#E9DDFF]">PORTAIL JEUNESSE</span>
                        <span aria-hidden="true" class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#8B5CF6]/70 bg-slate-900/70 text-2xl shadow-[0_0_16px_rgba(139,92,246,0.28)]">🎉</span>
                    </div>
                    <h3 class="text-3xl font-black tracking-tight text-white">Portail Jeunesse</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-200">
                        Activités, groupes, présences et engagement.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#E9DDFF]">
                        <span class="rounded-full border border-[#8B5CF6]/70 bg-[#8B5CF6]/10 px-2.5 py-1.5">Profil</span>
                        <span class="rounded-full border border-[#8B5CF6]/70 bg-[#8B5CF6]/10 px-2.5 py-1.5">Activités</span>
                        <span class="rounded-full border border-[#8B5CF6]/70 bg-[#8B5CF6]/10 px-2.5 py-1.5">Présences</span>
                    </div>
                    <div class="mt-6">
                        <a href="{{ auth()->check() ? route('dashboard.youth') : route('login') }}" class="inline-flex rounded-xl border border-[#8B5CF6]/80 bg-[#8B5CF6]/15 px-4 py-2.5 font-semibold text-[#F3EBFF] shadow-[0_0_16px_rgba(139,92,246,0.24)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#8B5CF6]/25">Accéder</a>
                    </div>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-[2rem] border border-[#22C55E]/80 bg-slate-950/90 p-6 text-white shadow-[0_30px_90px_rgba(34,197,94,0.22)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(134,239,172,0.18),transparent_18%),radial-gradient(circle_at_bottom_right,_rgba(21,128,61,0.26),transparent_28%)]"></div>
                <div class="absolute inset-x-4 top-0 h-px bg-[#22C55E]"></div>
                <div class="relative">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <span class="rounded-full border border-[#22C55E]/80 bg-[#22C55E]/15 px-3 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-[#DDFCE7]">PORTAIL ECODIM</span>
                        <span aria-hidden="true" class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#22C55E]/70 bg-slate-900/70 text-2xl shadow-[0_0_16px_rgba(34,197,94,0.28)]">🧒</span>
                    </div>
                    <h3 class="text-3xl font-black tracking-tight text-white">Portail ECODIM</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-200">
                        Enfants, classes, présences et parcours.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#DDFCE7]">
                        <span class="rounded-full border border-[#22C55E]/70 bg-[#22C55E]/10 px-2.5 py-1.5">Enfants</span>
                        <span class="rounded-full border border-[#22C55E]/70 bg-[#22C55E]/10 px-2.5 py-1.5">Classes</span>
                        <span class="rounded-full border border-[#22C55E]/70 bg-[#22C55E]/10 px-2.5 py-1.5">Transition</span>
                    </div>
                    <div class="mt-6">
                        <a href="{{ auth()->check() ? route('dashboard.ecodim') : route('login') }}" class="inline-flex rounded-xl border border-[#22C55E]/80 bg-[#22C55E]/15 px-4 py-2.5 font-semibold text-[#EAFEF0] shadow-[0_0_16px_rgba(34,197,94,0.24)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#22C55E]/25">Accéder</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== LIVE VIDÉO ==================== --}}
    <section class="home-video-section mt-10">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-2xl font-bold text-slate-900">
                📺 Culte en direct
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

        <p class="mb-4 text-sm text-slate-600">Retrouvez ici les directs disponibles.</p>

        @if ($live && $live->is_active && \App\Support\VideoEmbed::toEmbed($live->media_url))
            <div class="live-feed-shell">
                <div class="live-feed-grid">
                    <div class="video-feed-surface">
                        <div class="video-shell" data-video-player data-video-id="{{ \App\Support\VideoEmbed::youtubeId($live->media_url) }}">
                            <div class="aspect-video overflow-hidden bg-black">
                                <iframe src="{{ \App\Support\VideoEmbed::toEmbed($live->media_url) }}"
                                    class="video-embed-frame h-full w-full" style="border:0" tabindex="-1" sandbox="allow-scripts allow-same-origin allow-presentation"
                                        allow="autoplay; encrypted-media"
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
                                    <div class="live-social-pill live-social-pill-like cursor-not-allowed opacity-80" aria-label="Connectez-vous pour aimer cette vidéo">
                                        <span aria-hidden="true">♥</span>
                                        <span>{{ $liveArchive->likes_count }}</span>
                                    </div>
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
                                    @foreach ($liveArchive->comments as $comment)
                                        <div class="comment-bubble" data-comment-id="{{ $comment->id }}">
                                            <div class="comment-avatar">
                                                @if ($comment->user->profile_photo_url)
                                                    <img src="{{ $comment->user->profile_photo_url }}" alt="Photo de {{ $comment->user->full_name }}">
                                                @else
                                                    <span aria-hidden="true">{{ mb_substr($comment->user->full_name, 0, 1) }}</span>
                                                @endif
                                            </div>
                                            <div class="comment-content">
                                                <div class="comment-meta">
                                                    <span class="comment-user">{{ $comment->user->full_name }}</span>
                                                    <div class="comment-meta-actions">
                                                        <span class="comment-time">{{ $comment->created_at->diffForHumans() }}</span>
                                                        @auth
                                                            @if (auth()->id() === $comment->user_id)
                                                                <button type="button" class="comment-delete" data-comment-delete data-comment-id="{{ $comment->id }}" data-delete-url="{{ route('videos.comment.destroy', $comment) }}" aria-label="Supprimer ce commentaire" title="Supprimer ce commentaire">×</button>
                                                            @endif
                                                        @endauth
                                                    </div>
                                                </div>
                                                <p>{{ $comment->body }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="comment-stream empty" data-comments-list="{{ $liveArchive->id }}">
                                    @guest
                                        <div class="comment-empty-card">
                                            <div class="comment-empty-mark" aria-hidden="true">L</div>
                                            <div class="comment-empty-copy">
                                                <span class="comment-empty-label">LA COMMUNAUTÉ</span>
                                                <p class="comment-empty-title">Soyez le premier à commenter {{ $liveArchive->broadcast_type === 'replay' ? 'cette retransmission' : 'ce live' }}.</p>
                                                <p class="comment-empty-cta">Connectez-vous pour aimer ou commenter {{ $liveArchive->broadcast_type === 'replay' ? 'cette retransmission' : 'ce live' }}.</p>
                                                <div class="comment-login-links">
                                                    <a href="{{ route('login') }}">Se connecter</a>
                                                    <a href="{{ route('register') }}">Créer un compte</a>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="comment-empty-card comment-empty-card-authenticated">
                                            <div class="comment-empty-mark" aria-hidden="true">L</div>
                                            <div class="comment-empty-copy">
                                                <span class="comment-empty-label">LA COMMUNAUTÉ</span>
                                                <p class="comment-empty-title">Aucun commentaire pour le moment.</p>
                                                <p class="comment-empty-cta">Soyez le premier à partager votre pensée sur {{ $liveArchive->broadcast_type === 'replay' ? 'cette retransmission' : 'ce live' }}.</p>
                                            </div>
                                        </div>
                                    @endguest
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

    <p class="home-vision-tagline mx-auto mt-8 max-w-5xl text-center">
        Une communauté, trois parcours, une même vision : <span>ECODIM, Jeunesse, Église.</span>
    </p>

    <section class="youth-path mt-10" aria-labelledby="youth-path-title">
        <div class="youth-path-heading">
            <div>
                <p class="youth-path-kicker"><span></span> Notre fonctionnement</p>
                <h2 id="youth-path-title">Une seule communauté, un même parcours</h2>
                <p>De l’ECODIM à la Jeunesse, puis dans la vie de l’Église, chacun grandit, participe et avance au sein d’une même communauté.</p>
                </div>
                <div class="youth-path-code" aria-hidden="true">ECODIM / JEUNESSE / ÉGLISE</div>
        </div>

        <div class="youth-path-grid">
            <article class="youth-path-card">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">01</span>
                    <span class="youth-path-symbol" aria-hidden="true">✦</span>
                </div>
                    <h3>Grandir dans la foi</h3>
                    <p>La Parole de Dieu, la prière et l’enseignement nous accompagnent à chaque étape du parcours.</p>
                    <span class="youth-path-label">Foi · Prière · Enseignement</span>
            </article>

            <article class="youth-path-card">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">02</span>
                    <span class="youth-path-symbol" aria-hidden="true">◌</span>
                </div>
                    <h3>Vivre en communauté</h3>
                    <p>Enfants, jeunes et adultes grandissent ensemble dans l’accueil, la fraternité et la communion.</p>
                    <span class="youth-path-label">Accueil · Fraternité · Communauté</span>
            </article>

            <article class="youth-path-card">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">03</span>
                    <span class="youth-path-symbol" aria-hidden="true">⌁</span>
                </div>
                    <h3>Servir selon ses dons</h3>
                    <p>Chacun peut participer à la vie de l’Église selon ses talents, ses capacités et ses responsabilités.</p>
                    <span class="youth-path-label">Musique · Enseignement · Intercession · Service</span>
            </article>

            <article class="youth-path-card youth-path-card-featured">
                <div class="youth-path-card-top">
                    <span class="youth-path-index">04</span>
                    <span class="youth-path-symbol" aria-hidden="true">↗</span>
                </div>
                    <h3>Avancer dans son parcours</h3>
                    <p>Chaque étape s’inscrit dans une continuité : <strong>ECODIM → JEUNESSE → ÉGLISE</strong>, avec un accompagnement adapté à chaque parcours.</p>
                    <span class="youth-path-label">Parcours · Engagement · Responsabilités</span>
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
        let isDragging = false;
        let suppressClick = false;

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
                isDragging = true;
                carousel.setPointerCapture(event.pointerId);
            });

            carousel.addEventListener('pointermove', (event) => {
                if (!isDragging) return;
                dragDeltaX = event.clientX - dragStartX;
            });

            const finishDrag = () => {
                if (!isDragging) return;

                if (Math.abs(dragDeltaX) > 80) {
                    window.carouselGo(window.carouselIndex + (dragDeltaX < 0 ? 1 : -1), true);
                    suppressClick = true;
                }
                isDragging = false;
                dragStartX = 0;
                dragDeltaX = 0;
            };

            carousel.addEventListener('pointerup', finishDrag);
            carousel.addEventListener('pointercancel', finishDrag);
            carousel.addEventListener('lostpointercapture', finishDrag);

            carousel.addEventListener('click', (event) => {
                if (suppressClick) {
                    event.preventDefault();
                    event.stopPropagation();
                    suppressClick = false;
                }
            });

            carousel.addEventListener('mouseenter', () => window.clearInterval(carouselTimer));
            carousel.addEventListener('mouseleave', window.carouselStart);
            updateSlidePositions();
            window.carouselStart();
        }
    </script>

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

            if (listNode.classList.contains('empty')) {
                listNode.classList.remove('empty');
                listNode.innerHTML = '';
            }

            const item = document.createElement('div');
            item.className = 'comment-bubble';
            item.dataset.commentId = comment.id;

            const avatar = document.createElement('div');
            avatar.className = 'comment-avatar';

            if (comment.profile_photo_url) {
                const avatarImage = document.createElement('img');
                avatarImage.src = comment.profile_photo_url;
                avatarImage.alt = 'Photo de ' + comment.user;
                avatarImage.loading = 'lazy';
                avatar.appendChild(avatarImage);
            } else {
                avatar.textContent = (comment.user || 'U').slice(0, 1).toUpperCase();
            }

            const content = document.createElement('div');
            content.className = 'comment-content';

            const meta = document.createElement('div');
            meta.className = 'comment-meta';

            const user = document.createElement('span');
            user.className = 'comment-user';
            user.textContent = comment.user;

            const time = document.createElement('span');
            time.className = 'comment-time';
            time.textContent = comment.created_at;

            const actions = document.createElement('div');
            actions.className = 'comment-meta-actions';
            actions.append(time);

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'comment-delete';
            deleteButton.dataset.commentDelete = '';
            deleteButton.dataset.commentId = comment.id;
            deleteButton.dataset.deleteUrl = comment.delete_url;
            deleteButton.setAttribute('aria-label', 'Supprimer ce commentaire');
            deleteButton.title = 'Supprimer ce commentaire';
            deleteButton.textContent = '×';
            actions.append(deleteButton);

            const body = document.createElement('p');
            body.textContent = comment.body;

            meta.append(user, actions);
            content.append(meta, body);
            item.append(avatar, content);
            listNode.prepend(item);
        }

        function showEmptyCommentState(listNode) {
            if (!listNode) return;

            listNode.classList.add('empty');
            listNode.innerHTML = '<div class="comment-empty-card comment-empty-card-authenticated"><div class="comment-empty-mark" aria-hidden="true">L</div><div class="comment-empty-copy"><span class="comment-empty-label">LA COMMUNAUTÉ</span><p class="comment-empty-title">Aucun commentaire pour le moment.</p><p class="comment-empty-cta">Soyez le premier à partager votre pensée.</p></div></div>';
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

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-comment-delete]');

            if (!button || button.dataset.busy === 'true') return;
            if (!window.confirm('Supprimer ce commentaire ?')) return;

            button.dataset.busy = 'true';
            button.disabled = true;

            try {
                const response = await fetch(button.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                    },
                });

                if (!response.ok) throw new Error('Comment deletion failed');

                const data = await response.json();
                const item = button.closest('[data-comment-id]');
                const listNode = item?.closest('[data-comments-list]');
                const commentCountNode = listNode ? document.querySelector('[data-comments-count="' + listNode.dataset.commentsList + '"]') : null;

                item?.remove();
                if (commentCountNode) commentCountNode.textContent = data.count;
                if (listNode && !listNode.querySelector('[data-comment-id]')) showEmptyCommentState(listNode);
            } catch (error) {
                console.error(error);
                button.disabled = false;
                button.dataset.busy = 'false';
            }
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
        <section class="home-events-section mt-10">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">📅 Événements à venir</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($upcomingEvents as $event)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @if ($event->photo_url)
                            <img src="{{ $event->photo_url }}" alt="Affiche de {{ $event->name }}" class="h-48 w-full bg-slate-100 object-contain p-2" onerror="this.onerror=null; this.src='{{ asset('logoEglise.jpg') }}';">
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
        <section class="home-cta mt-12 rounded-3xl bg-slate-900 px-6 py-12 text-center text-white">
            <h2 class="text-2xl font-bold">Vous faites partie de la jeunesse ?</h2>
            <p class="mx-auto mt-2 max-w-xl text-slate-300">Créez votre compte pour suivre vos présences, consulter l'annuaire, la galerie et les événements. Votre compte sera validé par le Président responsable de la jeunesse de La Parole Éternelle, Centre-Ville de Kolwezi.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-xl bg-amber-400 px-6 py-3 font-semibold text-amber-950 hover:bg-amber-300">Créer mon compte</a>
                <a href="{{ route('login') }}" class="rounded-xl border border-slate-600 px-6 py-3 font-semibold hover:bg-slate-800">Se connecter</a>
            </div>
        </section>
    @endguest

@endsection
