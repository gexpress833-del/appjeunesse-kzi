<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\HomeContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PastorAnnouncementAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pastor_can_access_and_publish_announcements(): void
    {
        $church = Church::create([
            'name' => 'Église test',
            'slug' => 'eglise-test',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->actingAs($pastor)
            ->get(route('carousel.index'))
            ->assertOk();

        $response = $this->actingAs($pastor)
            ->post(route('carousel.store'), [
                'type' => 'event_banner',
                'source' => 'church',
                'title' => 'Annonce du pasteur',
                'content' => 'Message de la part du pasteur',
                'author_or_reference' => 'Pasteur principal',
                'is_active' => true,
                'display_order' => 1,
            ]);

        $response->assertRedirect(route('carousel.index'));
        $this->assertDatabaseHas('home_contents', [
            'title' => 'Annonce du pasteur',
            'type' => 'event_banner',
            'source' => 'church',
        ]);
        $this->assertInstanceOf(HomeContent::class, HomeContent::first());
    }
}
