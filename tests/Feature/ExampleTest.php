<?php

namespace Tests\Feature;

use App\Support\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_live_video_urls_are_converted_to_embed_urls(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?controls=0&disablekb=1&enablejsapi=1&fs=0&iv_load_policy=3&modestbranding=1&origin=http%3A%2F%2Flocalhost%3A8000&playsinline=1&rel=0',
            VideoEmbed::toEmbed('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        );

        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?controls=0&disablekb=1&enablejsapi=1&fs=0&iv_load_policy=3&modestbranding=1&origin=http%3A%2F%2Flocalhost%3A8000&playsinline=1&rel=0',
            VideoEmbed::toEmbed('https://youtu.be/dQw4w9WgXcQ')
        );

        $this->assertSame(
            'https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Ffacebook.com%2Fwatch%3Fv%3D1234567890&show_text=false&autoplay=false',
            VideoEmbed::toEmbed('https://facebook.com/watch?v=1234567890')
        );
    }

    public function test_live_video_embed_uses_the_current_request_origin(): void
    {
        config()->set('app.url', 'http://localhost:8000');
        app()->instance('request', \Illuminate\Http\Request::create('http://127.0.0.1:8000/'));

        $this->assertStringContainsString(
            'origin=http%3A%2F%2F127.0.0.1%3A8000',
            VideoEmbed::toEmbed('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        );
    }
}
