<?php

namespace App\Support;

class VideoEmbed
{
    /**
     * Convertit une URL YouTube/Facebook en URL intégrable.
     */
    public static function toEmbed(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        // YouTube : watch, live, shorts, embed, youtu.be, youtube-nocookie.
        if (preg_match('~(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|live\/|embed\/|shorts\/)|youtu\.be\/|youtube-nocookie\.com\/embed\/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        // Facebook : plugin vidéo officiel
        if (preg_match('~facebook\.com~i', $url)) {
            return 'https://www.facebook.com/plugins/video.php?href='.urlencode($url)
                .'&show_text=false&autoplay=false';
        }

        return $url;
    }

    /**
     * Plateforme détectée (pour l'icône).
     */
    public static function platform(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (preg_match('~youtu~i', $url)) {
            return 'youtube';
        }

        if (preg_match('~facebook~i', $url)) {
            return 'facebook';
        }

        return null;
    }
}
