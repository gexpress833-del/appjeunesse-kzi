<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoArchive;
use App\Models\VideoComment;
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

    public function test_secretariat_can_manage_archived_videos(): void
    {
        $secretariat = User::factory()->create(['role' => 'secretariat', 'status' => 'active']);

        $this->actingAs($secretariat)
            ->get(route('videos.manage'))
            ->assertOk()
            ->assertSee('Vidéos archivées');

        $this->actingAs($secretariat)
            ->post(route('videos.store'), [
                'title' => 'Vidéo du secrétariat',
                'description' => 'Vidéo publiée par le secrétariat.',
                'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'broadcast_type' => 'replay',
            ])
            ->assertRedirect(route('videos.manage'));

        $video = VideoArchive::query()->firstOrFail();

        $this->actingAs($secretariat)
            ->delete(route('videos.destroy', $video))
            ->assertRedirect(route('videos.manage'));

        $this->assertDatabaseMissing('video_archives', ['id' => $video->id]);
    }

    public function test_responsable_outside_media_cannot_manage_archived_videos(): void
    {
        $responsable = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'Social',
            'status' => 'active',
        ]);
        $video = VideoArchive::create([
            'title' => 'Vidéo protégée',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'broadcast_type' => 'replay',
        ]);

        $this->actingAs($responsable)
            ->get(route('videos.manage'))
            ->assertForbidden();

        $this->actingAs($responsable)
            ->delete(route('videos.destroy', $video))
            ->assertForbidden();
    }

    public function test_comment_owner_can_delete_own_comment_but_other_users_cannot(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $otherUser = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $video = VideoArchive::create([
            'title' => 'Retransmission test',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'broadcast_type' => 'replay',
        ]);
        $comment = VideoComment::create([
            'video_archive_id' => $video->id,
            'user_id' => $owner->id,
            'body' => 'Commentaire du propriétaire',
        ]);

        $this->actingAs($otherUser)
            ->deleteJson(route('videos.comment.destroy', $comment))
            ->assertForbidden();

        $this->assertDatabaseHas('video_comments', ['id' => $comment->id]);

        $this->actingAs($owner)
            ->deleteJson(route('videos.comment.destroy', $comment))
            ->assertOk()
            ->assertJson(['comment_id' => $comment->id, 'count' => 0]);

        $this->assertDatabaseMissing('video_comments', ['id' => $comment->id]);
    }

    public function test_comment_payload_includes_user_profile_photo_url(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'profile_photo_url' => 'https://example.com/users/avatar.jpg',
        ]);

        $video = VideoArchive::create([
            'title' => 'Direct test photo',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'broadcast_type' => 'replay',
        ]);

        $this->actingAs($user)
            ->postJson(route('videos.comment', $video), ['body' => 'Bonjour !'])
            ->assertOk()
            ->assertJsonPath('comment.profile_photo_url', 'https://example.com/users/avatar.jpg');
    }
}
