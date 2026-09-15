<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoArchiveManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_archived_videos(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('videos.manage'))
            ->assertOk()
            ->assertSee('Vidéos archivées');

        $this->actingAs($admin)
            ->post(route('videos.store'), [
                'title' => 'Culte de la jeunesse',
                'description' => 'Retransmission du samedi.',
                'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'broadcast_type' => 'replay',
            ])
            ->assertRedirect(route('videos.manage'));

        $video = VideoArchive::query()->firstOrFail();
        $this->assertSame($admin->id, $video->published_by);

        $this->actingAs($admin)
            ->put(route('videos.update', $video), [
                'title' => 'Culte de la jeunesse modifié',
                'description' => 'Version mise à jour.',
                'media_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
                'broadcast_type' => 'live',
            ])
            ->assertRedirect(route('videos.manage'));

        $this->assertDatabaseHas('video_archives', [
            'id' => $video->id,
            'title' => 'Culte de la jeunesse modifié',
            'broadcast_type' => 'live',
        ]);

        $this->actingAs($admin)
            ->delete(route('videos.destroy', $video))
            ->assertRedirect(route('videos.manage'));

        $this->assertDatabaseMissing('video_archives', ['id' => $video->id]);
    }

    public function test_dcc_responsable_can_manage_archived_videos(): void
    {
        $dccResponsible = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'Médias/DCC',
            'status' => 'active',
        ]);

        $this->actingAs($dccResponsible)
            ->get(route('videos.manage'))
            ->assertOk()
            ->assertSee('Vidéos archivées');

        $this->actingAs($dccResponsible)
            ->post(route('videos.store'), [
                'title' => 'Direct DCC',
                'description' => 'Vidéo de la communication.',
                'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'broadcast_type' => 'replay',
            ])
            ->assertRedirect(route('videos.manage'));

        $video = VideoArchive::query()->firstOrFail();

        $this->actingAs($dccResponsible)
            ->delete(route('videos.destroy', $video))
            ->assertRedirect(route('videos.manage'));

        $this->assertDatabaseMissing('video_archives', ['id' => $video->id]);
    }

    public function test_non_admin_cannot_manage_archived_videos(): void
    {
        $secretariat = User::factory()->create(['role' => 'secretariat', 'status' => 'active']);
        $video = VideoArchive::create([
            'title' => 'Vidéo protégée',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'broadcast_type' => 'replay',
        ]);

        $this->actingAs($secretariat)
            ->get(route('videos.manage'))
            ->assertForbidden();

        $this->actingAs($secretariat)
            ->delete(route('videos.destroy', $video))
            ->assertForbidden();
    }
}
