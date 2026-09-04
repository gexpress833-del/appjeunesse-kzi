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

        // YouTube : youtube.com/watch?v=ID, youtu.be/ID, youtube.com/live/ID
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|live/|embed/)|youtu\.be/)([\w-]{6,20})~i', $url, $m)) {
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
