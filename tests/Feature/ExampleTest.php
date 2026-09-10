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
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1&playsinline=1',
            VideoEmbed::toEmbed('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        );

        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1&playsinline=1',
            VideoEmbed::toEmbed('https://youtu.be/dQw4w9WgXcQ')
        );

        $this->assertSame(
            'https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Ffacebook.com%2Fwatch%3Fv%3D1234567890&show_text=false&autoplay=false',
            VideoEmbed::toEmbed('https://facebook.com/watch?v=1234567890')
        );
    }
}
