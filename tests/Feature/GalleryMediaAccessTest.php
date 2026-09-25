<?php

namespace Tests\Feature;

use App\Models\Church;
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

    public function test_admin_secretariat_and_dcc_responsable_can_open_photo_upload_form(): void
    {
        $profiles = [
            User::factory()->create(['role' => 'admin', 'status' => 'active']),
            User::factory()->create(['role' => 'secretariat', 'status' => 'active']),
            User::factory()->create(['role' => 'responsable', 'dept' => 'Médias/DCC', 'status' => 'active']),
        ];

        foreach ($profiles as $profile) {
            $this->actingAs($profile)
                ->get(route('gallery.upload'))
                ->assertOk()
                ->assertSee('Département chrétien de communication (DCC)')
                ->assertSee('Événement associé');
        }

        $otherResponsable = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'Social',
            'status' => 'active',
        ]);

        $this->actingAs($otherResponsable)
            ->get(route('gallery.upload'))
            ->assertForbidden();
    }

    public function test_secretariat_can_publish_photos_for_an_event(): void
    {
        $secretariat = User::factory()->create(['role' => 'secretariat', 'status' => 'active']);
        $event = Event::create([
            'name' => 'Culte du samedi',
            'date' => '2026-08-12 09:00:00',
            'description' => 'Retraite annuelle 2026',
        ]);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
            $mock->shouldReceive('upload')->once()->andReturn([
                'url' => 'https://example.com/secretariat-photo.jpg',
                'public_id' => 'gallery-secretariat-photo',
            ]);
        });

        $this->actingAs($secretariat)
            ->post(route('gallery.store'), [
                'photos' => [UploadedFile::fake()->image('culte.jpg')],
                'title' => 'R26',
                'description' => 'Retraite annuelle 2026',
                'event_id' => $event->id,
            ])
            ->assertRedirect(route('gallery.index'));

        $this->assertDatabaseHas('photos', [
            'event_id' => $event->id,
            'event_name' => 'Culte du samedi',
            'uploaded_by' => $secretariat->username,
        ]);
    }

    public function test_gallery_groups_photos_by_event_with_normalized_metadata(): void
    {
        $church = Church::create([
            'name' => 'Église de la galerie',
            'slug' => 'eglise-de-la-galerie',
            'type' => 'main',
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'church_id' => $church->id]);

        $this->actingAs($admin)
            ->post(route('events.store'), [
                'name' => '  CULTE DU   SAMEDI  ',
                'date' => '2026-08-12 09:00:00',
                'description' => 'Retraite annuelle 2026',
            ])
            ->assertRedirect(route('events.index'));

        $event = Event::query()->firstOrFail();
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        Photo::create([
            'title' => 'R26',
            'image_url' => 'https://example.com/r26.jpg',
            'cloudinary_public_id' => 'gallery-r26',
            'event_id' => $event->id,
            'event_name' => $event->name,
            'uploaded_by' => 'alice',
        ]);

        $this->assertSame('Culte du samedi', $event->name);

        $this->actingAs($user)
            ->get(route('gallery.index'))
            ->assertOk()
            ->assertSee('Culte du samedi')
            ->assertSee('12/08/2026')
            ->assertSee('Retraite annuelle 2026')
            ->assertSee('1 photo');
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

    public function test_media_manager_can_publish_fifty_photos_for_one_event(): void
    {
        $manager = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Médias/DCC',
        ]);
        $event = Event::create([
            'name' => 'Album des cinquante photos',
            'date' => now()->addDay(),
            'created_by' => $manager->username,
        ]);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
            $mock->shouldReceive('upload')->times(50)->andReturnUsing(
                fn (UploadedFile $file): array => [
                    'url' => 'https://example.com/'.$file->getClientOriginalName(),
                    'public_id' => 'gallery-'.$file->getClientOriginalName(),
                ],
            );
        });

        $this->actingAs($manager)
            ->post(route('gallery.store'), [
                'photos' => array_map(
                    fn (int $index): UploadedFile => UploadedFile::fake()->image('photo-'.$index.'.jpg'),
                    range(1, 50),
                ),
                'event_id' => $event->id,
            ])
            ->assertRedirect(route('gallery.index'))
            ->assertSessionHas('success', '50 photo(s) publiée(s) pour l\'événement "Album des cinquante photos".');

        $this->assertDatabaseCount('photos', 50);
    }
}
