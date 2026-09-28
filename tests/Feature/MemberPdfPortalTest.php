<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as DomPdfFacade;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPdfPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_pdf_only_includes_church_attendances_and_labels_the_scope(): void
    {
        Department::create(['name' => 'Social']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = Member::create([
            'name' => 'Membre multi-portail',
            'dept' => 'Social',
            'role' => 'Membre',
            'email' => 'multi.portal@example.com',
        ]);
        $churchEvent = Event::create([
            'name' => 'Culte dominical',
            'date' => '2026-09-27 09:00:00',
            'created_by' => $admin->username,
            'dept' => 'Social',
            'portal' => 'church',
        ]);
        $youthEvent = Event::create([
            'name' => 'Rencontre jeunesse',
            'date' => '2026-09-27 14:00:00',
            'created_by' => $admin->username,
            'dept' => 'Social',
            'portal' => 'youth',
        ]);
        Attendance::create(['member_id' => $member->id, 'event_id' => $churchEvent->id, 'status' => 'present']);
        Attendance::create(['member_id' => $member->id, 'event_id' => $youthEvent->id, 'status' => 'absent']);

        $viewData = [];
        $pdf = \Mockery::mock(PDF::class);
        $pdf->shouldReceive('loadView')
            ->once()
            ->with('members.pdf', \Mockery::on(function (array $data) use (&$viewData): bool {
                $viewData = $data;

                return true;
            }))
            ->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('PDF test', 200, ['Content-Type' => 'application/pdf']));
        DomPdfFacade::shouldReceive('setOption')->once()->andReturn($pdf);

        $this->actingAs($admin)->get(route('members.pdf', $member))->assertOk();

        $this->assertSame('PORTAIL ÉGLISE', $viewData['portalLabel']);
        $this->assertSame('Église - Département Social', $viewData['reportScope']);
        $this->assertCount(1, $viewData['attendances']);
        $this->assertSame($churchEvent->id, $viewData['attendances']->first()->event_id);
        $this->assertSame(1, $viewData['total']);

        $html = view('members.pdf', $viewData)->render();
        $this->assertStringContainsString('PORTAIL ÉGLISE', $html);
        $this->assertStringContainsString('Église - Département Social', $html);
        $this->assertStringContainsString('Culte dominical', $html);
        $this->assertStringNotContainsString('Rencontre jeunesse', $html);
    }
}
