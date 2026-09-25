<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Department;
use App\Models\MemberRoleAssignment;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.index', [
            'settings' => AppSetting::current(),
            'departments' => Department::query()->with(['leader', 'leaderAssignedBy'])->withCount('members')->orderBy('name')->get(),
            'eligibleLeaders' => $this->eligibleLeaders($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $rules = [
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:1000'],
            'attendance_statuses' => ['required', 'array', 'min:1'],
            'attendance_statuses.*' => ['required', 'in:present,absent,late,excused'],
            'attendance_editable_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'new_registration' => ['nullable', 'boolean'],
            'event_created' => ['nullable', 'boolean'],
            'attendance_recorded' => ['nullable', 'boolean'],
            'social_visit' => ['nullable', 'boolean'],
            'video_comment' => ['nullable', 'boolean'],
            'carousel_enabled' => ['nullable', 'boolean'],
            'events_enabled' => ['nullable', 'boolean'],
            'video_comments_enabled' => ['nullable', 'boolean'],
            'video_likes_enabled' => ['nullable', 'boolean'],
        ];

        if ($user->isAdmin()) {
            $rules += [
                'church_name' => ['required', 'string', 'max:150'],
                'application_name' => ['required', 'string', 'max:100'],
                'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())],
                'maintenance_mode' => ['nullable', 'boolean'],
            ];
        }

        $data = $request->validate($rules);
        $settings = AppSetting::current();

        $settings->fill([
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'attendance_statuses' => array_values(array_unique($data['attendance_statuses'])),
            'attendance_editable_hours' => $data['attendance_editable_hours'],
            'notification_settings' => $this->checkboxes($data, [
                'new_registration', 'event_created', 'attendance_recorded', 'social_visit', 'video_comment',
            ]),
            'communication_settings' => $this->checkboxes($data, [
                'carousel_enabled', 'events_enabled', 'video_comments_enabled', 'video_likes_enabled',
            ]),
        ]);

        if ($user->isAdmin()) {
            $settings->fill([
                'church_name' => $data['church_name'],
                'application_name' => $data['application_name'],
                'timezone' => $data['timezone'],
                'maintenance_mode' => (bool) ($data['maintenance_mode'] ?? false),
            ]);
        }

        $settings->save();

        return back()->with('success', 'Paramètres enregistrés.');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $this->authorizeDepartmentManagement($request);

        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:departments,name']]);
        Department::create($data);

        return back()->with('success', 'Département ajouté.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeDepartmentManagement($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department->id)],
        ]);

        DB::transaction(function () use ($department, $data): void {
            $oldName = $department->name;
            $department->update(['name' => $data['name']]);
            DB::table('members')->where('dept', $oldName)->update(['dept' => $data['name']]);
            DB::table('users')->where('dept', $oldName)->update(['dept' => $data['name']]);
            DB::table('events')->where('dept', $oldName)->update(['dept' => $data['name']]);
        });

        return back()->with('success', 'Département renommé.');
    }

    public function destroyDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeDepartmentManagement($request);

        if ($department->members()->exists()
            || DB::table('users')->where('dept', $department->name)->exists()
            || DB::table('events')->where('dept', $department->name)->exists()) {
            throw ValidationException::withMessages([
                'department' => 'Ce département est utilisé et ne peut pas être supprimé. Renommez-le ou désactivez-le plutôt.',
            ]);
        }

        $department->delete();

        return back()->with('success', 'Département supprimé.');
    }

    public function assignDepartmentLeader(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->isPastorPrincipal(), 403, 'Seul le pasteur principal peut nommer un responsable de département.');

        $data = $request->validate([
            'leader_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $leader = isset($data['leader_user_id'])
            ? $request->user()->newQuery()->whereKey($data['leader_user_id'])->where('status', 'active')->where('church_id', $request->user()->church_id)->firstOrFail()
            : null;

        DB::transaction(function () use ($department, $leader, $request): void {
            $previousLeader = $department->leader()->first();

            $department->update([
                'leader_user_id' => $leader?->id,
                'leader_assigned_by' => $request->user()->id,
                'leader_assigned_at' => $leader ? now() : null,
            ]);

            if (! $leader) {
                if ($previousLeader && $previousLeader->dept === $department->name) {
                    $previousLeader->update(['dept' => null]);
                }

                return;
            }

            if ($previousLeader && $previousLeader->isNot($leader) && $previousLeader->dept === $department->name) {
                $previousLeader->update(['dept' => null]);
            }

            $leader->update(['dept' => $department->name]);

            $roleSlug = match ($department->code) {
                'youth' => 'responsable_jeunesse',
                'ecodim' => 'responsable_ecodim',
                default => 'responsable',
            };

            $role = Role::firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => str($roleSlug)->replace('_', ' ')->title(), 'status' => 'active'],
            );

            MemberRoleAssignment::query()
                ->where('scope_id', $department->id)
                ->where('scope_type', $department->code ?: 'department')
                ->where('status', 'active')
                ->update(['status' => 'inactive', 'ends_at' => now()]);

            MemberRoleAssignment::create([
                'user_id' => $leader->id,
                'role_id' => $role->id,
                'scope_type' => $department->code ?: 'department',
                'scope_id' => $department->id,
                'status' => 'active',
                'starts_at' => now(),
                'assigned_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', $leader ? 'Responsable de département nommé par le pasteur.' : 'Responsable de département retiré.');
    }

    private function eligibleLeaders(Request $request)
    {
        return $request->user()->newQuery()
            ->where('status', 'active')
            ->where('church_id', $request->user()->church_id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'role', 'dept']);
    }

    private function authorizeDepartmentManagement(Request $request): void
    {
        abort_unless($request->user()?->isChurchAdministrator(), 403, 'Seuls le pasteur, l’administrateur et le secrétariat peuvent gérer les départements.');
    }

    /** @return array<string, bool> */
    private function checkboxes(array $data, array $keys): array
    {
        return collect($keys)->mapWithKeys(fn (string $key): array => [$key => (bool) ($data[$key] ?? false)])->all();
    }
}
