@extends('layouts.guest')

@section('title', 'Archives vidéos')

@section('content')
    <section class="archive-hero rounded-[2rem] border border-white/10 bg-slate-950/55 p-6 shadow-[0_25px_80px_rgba(15,23,42,0.35)] backdrop-blur-xl">
        <div class="archive-hero-grid" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="archive-kicker"><span class="archive-status-dot"></span> Flux média sécurisé <span class="archive-kicker-line"></span> 2026</div>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.24em] text-cyan-300">Médiathèque</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-white md:text-5xl">Archives vidéo<span class="text-cyan-400">.</span></h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">Retrouvez les directs et retransmissions de la communauté dans une bibliothèque vivante, accessible à tout moment.</p>
            </div>

            <div class="archive-filter-dock flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('videos.archive') }}" class="archive-filter {{ $type === 'all' ? 'is-active' : '' }}">Tout</a>
                <a href="{{ route('videos.archive', ['type' => 'live']) }}" class="archive-filter {{ $type === 'live' ? 'is-active' : '' }}">En direct</a>
                <a href="{{ route('videos.archive', ['type' => 'replay']) }}" class="archive-filter {{ $type === 'replay' ? 'is-active' : '' }}">Retransmission</a>
            </div>
        </div>

        <div class="relative mt-8 grid gap-4 md:grid-cols-3">
            <div class="archive-stat-card">
            <span><i class="archive-stat-icon">◈</i> Total</span>
                <strong>{{ $totalVideos }}</strong>
                <small>vidéos</small>
            </div>
            <div class="archive-stat-card">
                <span><i class="archive-stat-icon">◉</i> Vues</span>
                <strong>{{ number_format($totalViews, 0, ',', ' ') }}</strong>
                <small>au total</small>
            </div>
            <div class="archive-stat-card">
                <span><i class="archive-stat-icon">✦</i> Likes</span>
                <strong>{{ number_format($totalLikes, 0, ',', ' ') }}</strong>
                <small>appréciations</small>
            </div>
        </div>
    </section>

    <section class="mt-8">
        @if ($videoArchives->isEmpty())
            <div class="rounded-[1.6rem] border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-slate-500">
                <p class="text-4xl">📼</p>
                <p class="mt-3 text-xl font-semibold text-slate-700">Aucune vidéo dans cette catégorie.</p>
                <p class="mt-2 text-sm">Les archives seront disponibles dès qu’une retransmission ou un direct sera publié.</p>
            </div>
        @else
            <div class="grid gap-6 xl:grid-cols-2">
                @foreach ($videoArchives as $video)
                    <article class="archive-video-card group">
                        <div class="video-shell archive-video-shell" data-video-player data-video-id="{{ \App\Support\VideoEmbed::youtubeId($video->media_url) }}">
                            <div class="aspect-video overflow-hidden bg-black">
                                <iframe src="{{ \App\Support\VideoEmbed::toEmbed($video->media_url) }}"
                                    class="video-embed-frame h-full w-full" style="border:0" loading="lazy" tabindex="-1" sandbox="allow-scripts allow-same-origin allow-presentation"
                                        allow="autoplay; encrypted-media" title="{{ $video->title }}"></iframe>
                            </div>
                            @if (\App\Support\VideoEmbed::youtubeId($video->media_url))
                                <button type="button" class="video-play-button" data-video-toggle aria-label="Lire la vidéo">▶</button>
                            @endif
                        </div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span class="archive-badge {{ $video->broadcast_type === 'replay' ? 'replay' : 'live' }}">
                                        <span class="archive-badge-dot"></span>
                                        {{ $video->broadcast_type === 'replay' ? 'Retransmission' : 'En direct' }}
                                    </span>
                                    <h2 class="mt-3 text-xl font-bold text-white">{{ $video->title }}</h2>
                                </div>
                                <span class="text-xs text-slate-400">{{ $video->created_at->diffForHumans() }}</span>
                            </div>

                            @if ($video->description)
                                <p class="mt-3 text-sm leading-6 text-slate-300">{{ $video->description }}</p>
                            @endif

                            <div class="mt-4 grid gap-3 text-sm text-slate-300 sm:grid-cols-3">
                                <div class="archive-meta-box"><span class="archive-meta-icon">♥</span><strong>{{ $video->likes_count ?? 0 }}</strong><small>likes</small></div>
                                <div class="archive-meta-box"><span class="archive-meta-icon">◌</span><strong>{{ $video->comments_count ?? 0 }}</strong><small>commentaires</small></div>
                                <div class="archive-meta-box"><span class="archive-meta-icon">◉</span><strong>{{ $video->views_count }}</strong><small>vues</small></div>
                            </div>

                            @if ($video->publisher)
                                <div class="mt-4 text-xs uppercase tracking-[0.18em] text-slate-400">
                                    Publié par {{ $video->publisher->full_name }}
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
