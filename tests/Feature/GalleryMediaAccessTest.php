<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GalleryMediaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_orders_newest_first_and_can_filter_by_event(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $olderEvent = Event::create([
            'name' => 'Culte de janvier',
            'date' => '2025-01-15',
            'description' => 'Ancien événement',
        ]);

        $newerEvent = Event::create([
            'name' => 'Sortie de printemps',
            'date' => '2026-05-20',
            'description' => 'Événement récent',
        ]);

        $olderPhoto = Photo::create([
            'title' => 'Ancienne photo',
            'description' => 'Photo ancienne',
            'image_url' => 'https://example.com/old.jpg',
            'cloudinary_public_id' => 'old-public-id',
            'event_id' => $olderEvent->id,
            'event_name' => $olderEvent->name,
            'uploaded_by' => 'alice',
            'created_at' => '2025-01-16 08:00:00',
            'updated_at' => '2025-01-16 08:00:00',
        ]);

        $newerPhoto = Photo::create([
            'title' => 'Nouvelle photo',
            'description' => 'Photo récente',
            'image_url' => 'https://example.com/new.jpg',
            'cloudinary_public_id' => 'new-public-id',
            'event_id' => $newerEvent->id,
            'event_name' => $newerEvent->name,
            'uploaded_by' => 'bob',
            'created_at' => '2026-05-21 08:00:00',
            'updated_at' => '2026-05-21 08:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('gallery.index'))
            ->assertOk()
            ->assertSeeInOrder([$newerPhoto->title, $olderPhoto->title]);

        $this->actingAs($user)
            ->get(route('gallery.index', ['event_id' => $olderEvent->id]))
            ->assertOk()
            ->assertSee('Ancienne photo')
            ->assertDontSee('Nouvelle photo');
    }

    public function test_media_manager_must_link_upload_to_an_event(): void
    {
        $manager = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Médias/DCC',
        ]);

        $this->actingAs($manager)
            ->post(route('gallery.store'), [
                'title' => 'Photo sans événement',
                'description' => 'Test',
            ])
            ->assertSessionHasErrors(['event_id']);
    }

    public function test_media_manager_can_publish_multiple_photos_for_one_event(): void
    {
        $manager = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Médias/DCC',
        ]);
        $event = Event::create([
            'name' => 'Culte photo',
            'date' => now()->addDay(),
            'created_by' => $manager->username,
        ]);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
            $mock->shouldReceive('upload')->twice()->andReturn(
                ['url' => 'https://example.com/one.jpg', 'public_id' => 'gallery-one'],
                ['url' => 'https://example.com/two.jpg', 'public_id' => 'gallery-two'],
            );
        });

        $this->actingAs($manager)
            ->post(route('gallery.store'), [
                'photos' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.jpg'),
                ],
                'title' => 'Culte en images',
                'event_id' => $event->id,
            ])
            ->assertRedirect(route('gallery.index'))
            ->assertSessionHas('success', '2 photo(s) publiée(s) pour l\'événement "Culte photo".');

        $this->assertDatabaseCount('photos', 2);
        $this->assertDatabaseHas('photos', ['cloudinary_public_id' => 'gallery-one', 'event_id' => $event->id]);
        $this->assertDatabaseHas('photos', ['cloudinary_public_id' => 'gallery-two', 'event_id' => $event->id]);
    }
}
