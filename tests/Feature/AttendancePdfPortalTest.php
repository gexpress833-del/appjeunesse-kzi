<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePdfPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_church_pdf_is_rendered_by_dompdf(): void
    {
        Department::create(['name' => 'Social']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $event = Event::create([
            'name' => 'Culte dominical',
            'date' => '2026-09-27 09:00:00',
            'created_by' => $admin->username,
            'dept' => 'Social',
            'portal' => 'church',
        ]);
        $member = Member::create(['name' => 'Membre Église', 'dept' => 'Social', 'role' => 'Membre']);
        Attendance::create(['member_id' => $member->id, 'event_id' => $event->id, 'status' => 'present']);

        $response = $this->actingAs($admin)->get(route('attendances.pdf', [
            'event_id' => $event->id,
            'dept' => 'Social',
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_church_pdf_identifies_its_portal_scope_and_excludes_youth_rows(): void
    {
        Department::create(['name' => 'Social']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $leader = User::factory()->create([
            'full_name' => 'Jean Responsable Église',
            'role' => 'responsable',
            'dept' => 'Social',
            'status' => 'active',
        ]);
        $churchEvent = Event::create([
            'name' => 'Culte dominical',
            'date' => '2026-09-27 09:00:00',
            'created_by' => $leader->username,
            'dept' => 'Social',
            'portal' => 'church',
        ]);
        $youthEvent = Event::create([
            'name' => 'Rencontre jeunesse',
            'date' => '2026-09-27 14:00:00',
            'created_by' => 'jeunesse',
            'dept' => 'Social',
            'portal' => 'youth',
        ]);
        $churchMember = Member::create(['name' => 'Membre Église', 'dept' => 'Social', 'role' => 'Membre']);
        $youthMember = Member::create(['name' => 'Membre Jeunesse', 'dept' => 'Social', 'role' => 'Membre']);
        Attendance::create(['member_id' => $churchMember->id, 'event_id' => $churchEvent->id, 'status' => 'present']);
        Attendance::create(['member_id' => $youthMember->id, 'event_id' => $youthEvent->id, 'status' => 'absent']);

        $viewData = [];
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')
            ->once()
            ->with('attendances.pdf', \Mockery::on(function (array $data) use (&$viewData): bool {
                $viewData = $data;

                return true;
            }))
            ->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('PDF test', 200, ['Content-Type' => 'application/pdf']));
        Pdf::shouldReceive('setOption')->once()->andReturn($pdf);

        $response = $this->actingAs($admin)->get(route('attendances.pdf', [
            'event_id' => $churchEvent->id,
            'dept' => 'Social',
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame('church', $viewData['portal']);
        $this->assertSame('PORTAIL ÉGLISE', $viewData['portalLabel']);
        $this->assertSame('Église - Département Social', $viewData['reportScope']);
        $this->assertSame('Culte dominical', $viewData['eventName']);
        $this->assertSame('Jean Responsable Église', $viewData['responsibleName']);
        $this->assertCount(1, $viewData['rows']);
        $this->assertSame(1, (int) $viewData['summary']->sum('total'));
        $this->assertSame($churchEvent->id, $viewData['rows']->first()->event_id);

        $html = view('attendances.pdf', $viewData)->render();
        $this->assertStringContainsString('PORTAIL ÉGLISE', $html);
        $this->assertStringContainsString('Église - Département Social', $html);
        $this->assertStringContainsString('Événement :', $html);
        $this->assertStringContainsString('Culte dominical', $html);
        $this->assertStringContainsString('Jean Responsable Église', $html);
        $this->assertStringContainsString('Membre Église', $html);
        $this->assertStringNotContainsString('Membre Jeunesse', $html);

        $this->actingAs($admin)
            ->get(route('attendances.pdf', ['event_id' => $youthEvent->id, 'dept' => 'Social']))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('attendances.pdf', ['event_id' => $churchEvent->id, 'dept' => 'Chorale']))
            ->assertForbidden();
    }

    public function test_youth_pdf_identifies_its_portal_scope_and_excludes_church_rows(): void
    {
        $department = Department::create(['name' => 'Social']);
        $leader = User::factory()->create([
            'full_name' => 'Jean Responsable Jeunesse',
            'role' => 'user',
            'status' => 'active',
        ]);
        $role = Role::create([
            'name' => 'Responsable jeunesse',
            'slug' => 'responsable_jeunesse',
            'status' => 'active',
        ]);
        MemberRoleAssignment::create([
            'user_id' => $leader->id,
            'role_id' => $role->id,
            'scope_type' => 'youth',
            'scope_id' => $department->id,
            'status' => 'active',
        ]);
        $churchEvent = Event::create([
            'name' => 'Culte dominical',
            'date' => '2026-09-27 09:00:00',
            'created_by' => 'pasteur',
            'dept' => 'Social',
            'portal' => 'church',
        ]);
        $youthEvent = Event::create([
            'name' => 'Rencontre Jeunesse',
            'date' => '2026-09-27 14:00:00',
            'created_by' => $leader->username,
            'dept' => 'Social',
            'portal' => 'youth',
        ]);
        $churchMember = Member::create(['name' => 'Membre Église', 'dept' => 'Social', 'role' => 'Membre']);
        $youthMember = Member::create(['name' => 'Membre Jeunesse', 'dept' => 'Social', 'role' => 'Membre']);
        Attendance::create(['member_id' => $churchMember->id, 'event_id' => $churchEvent->id, 'status' => 'present']);
        Attendance::create(['member_id' => $youthMember->id, 'event_id' => $youthEvent->id, 'status' => 'absent']);

        $viewData = [];
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')
            ->once()
            ->with('attendances.pdf', \Mockery::on(function (array $data) use (&$viewData): bool {
                $viewData = $data;

                return true;
            }))
            ->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('PDF test', 200, ['Content-Type' => 'application/pdf']));
        Pdf::shouldReceive('setOption')->once()->andReturn($pdf);

        $response = $this->actingAs($leader)->get(route('youth.attendances.pdf', [
            'event_id' => $youthEvent->id,
            'dept' => 'Social',
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame('youth', $viewData['portal']);
        $this->assertSame('PORTAIL JEUNESSE', $viewData['portalLabel']);
        $this->assertSame('Jeunesse - Département Social', $viewData['reportScope']);
        $this->assertSame('Rencontre Jeunesse', $viewData['eventName']);
        $this->assertSame('Jean Responsable Jeunesse', $viewData['responsibleName']);
        $this->assertCount(1, $viewData['rows']);
        $this->assertSame(1, (int) $viewData['summary']->sum('total'));
        $this->assertSame($youthEvent->id, $viewData['rows']->first()->event_id);

        $html = view('attendances.pdf', $viewData)->render();
        $this->assertStringContainsString('PORTAIL JEUNESSE', $html);
        $this->assertStringContainsString('Jeunesse - Département Social', $html);
        $this->assertStringContainsString('Rencontre Jeunesse', $html);
        $this->assertStringContainsString('Jean Responsable Jeunesse', $html);
        $this->assertStringContainsString('Membre Jeunesse', $html);
        $this->assertStringNotContainsString('Membre Église', $html);
    }
}
