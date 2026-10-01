<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Department;
use App\Models\MemberRoleAssignment;
use App\Models\Membership;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\AccountValidated;
use App\Notifications\RoleUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Liste complète des comptes — admin uniquement.
     */
    public function index(Request $request)
    {
        return $this->accountIndex($request);
    }

    public function youthIndex(Request $request)
    {
        abort_unless($request->user()->canApproveYouthAccounts(), 403);

        return $this->accountIndex($request, 'youth');
    }

    private function accountIndex(Request $request, ?string $portal = null)
    {
        $users = User::query()
            ->when($portal === 'youth', function ($query): void {
                $youthDepartmentName = Department::query()->where('code', 'youth')->value('name');
                $youthMembershipUserIds = Membership::query()
                    ->where('type', 'youth')
                    ->where('status', 'active')
                    ->select('user_id');
                $youthRoleUserIds = MemberRoleAssignment::query()
                    ->whereIn('scope_type', ['youth', 'Youth'])
                    ->where('status', 'active')
                    ->select('user_id');

                $query->where('status', 'pending')
                    ->where(function ($query) use ($youthDepartmentName, $youthMembershipUserIds, $youthRoleUserIds): void {
                        $query->whereIn('id', $youthMembershipUserIds)
                            ->orWhereIn('id', $youthRoleUserIds);

                        if ($youthDepartmentName !== null) {
                            $query->orWhere('dept', $youthDepartmentName);
                        }
                    });
            })
            ->when($portal !== 'youth' && $request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderBy('full_name')
            ->paginate(25)->withQueryString();

        return view('users.index', [
            'users' => $users,
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['status', 'role']),
            'isYouthApprovalPage' => $portal === 'youth',
            'canCreateAccount' => $portal === null,
            'canManageRoles' => $portal === null && ($request->user()->isAdmin() || $request->user()->isPastorPrincipal()),
            'approvalRoute' => $portal === 'youth' ? 'youth.users.validate' : 'users.validate',
        ]);
    }

    /**
     * Création d'un compte par le secrétariat ou l'admin (statut 'pending').
     */
    public function create()
    {
        return view('users.form', [
            'departments' => Department::orderBy('name')->get(),
            'canAssignAllRoles' => auth()->user()->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['status'] = 'pending';
        $data['created_by'] = auth()->user()->username;

        $createdBy = $request->user();
        $createdUser = DB::transaction(function () use ($data, $createdBy): User {
            $createdUser = User::create($data);
            $portalDepartment = filled($createdUser->dept)
                ? Department::query()->where('name', $createdUser->dept)->whereIn('code', ['youth', 'ecodim'])->first()
                : null;

            if ($portalDepartment) {
                Membership::query()->create([
                    'user_id' => $createdUser->id,
                    'type' => $portalDepartment->code,
                    'entity_id' => $portalDepartment->id,
                    'status' => 'active',
                ]);
            }

            if ($createdUser->role === 'responsable' && $portalDepartment) {
                $roleSlug = $portalDepartment->code === 'youth' ? 'responsable_jeunesse' : 'responsable_ecodim';
                $role = Role::query()->firstOrCreate(
                    ['slug' => $roleSlug],
                    ['name' => $portalDepartment->code === 'youth' ? 'Responsable jeunesse' : 'Responsable ECODIM', 'status' => 'active'],
                );

                MemberRoleAssignment::query()->create([
                    'user_id' => $createdUser->id,
                    'role_id' => $role->id,
                    'scope_type' => $portalDepartment->code,
                    'scope_id' => $portalDepartment->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'assigned_by' => $createdBy->id,
                ]);
            }

            return $createdUser;
        });

        if (data_get(AppSetting::current()->notification_settings, 'new_registration', true)) {
            User::query()
                ->where('church_id', $createdBy->church_id)
                ->where('status', 'active')
                ->whereIn('role', ['admin', 'secretariat'])
                ->get()
                ->each(fn (User $recipient) => $recipient->notify(new AccountCreated($createdUser, $createdBy)));
        }

        return redirect()->route($createdBy->isAdmin() ? 'users.index' : 'dashboard')
            ->with('success', 'Compte créé pour '.$data['full_name'].' — en attente de validation par l\'administrateur.');
    }

    /**
     * Validation d'un compte en attente : pending -> active.
     */
    public function validateAccount(Request $request, User $user)
    {
        abort_unless($request->user()->isChurchAdministrator(), 403);

        return $this->activateAccount($request->user(), $user);
    }

    public function validateYouthAccount(Request $request, User $user)
    {
        abort_unless($request->user()->canApproveYouthAccounts(), 403);
        abort_unless($user->status === 'pending' && $user->belongsToPortal('youth'), 403);

        return $this->activateAccount($request->user(), $user);
    }

    private function activateAccount(User $approver, User $user)
    {
        abort_unless($user->status === 'pending', 403, 'Seuls les comptes en attente peuvent être validés.');

        $user->update([
            'status' => 'active',
            'role_assigned_by' => $approver->username,
            'role_assigned_at' => now(),
        ]);
        $user->notify(new AccountValidated);

        return back()->with('success', 'Compte de '.$user->full_name.' validé.');
    }

    /**
     * Attribution du rôle (et du département supervisé pour un responsable).
     */
    public function assignRole(Request $request, User $user)
    {
        $this->ensurePrimaryAdminIsProtected($user);

        $data = $request->validate([
            'role' => ['required', 'in:admin,secretariat,responsable,pasteur_n1,user'],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
        ]);

        if ($user->isPrimaryAdmin() && $user->is(auth()->user()) && $data['role'] !== 'admin') {
            abort(403, 'L’administrateur principal ne peut pas retirer ses propres droits administrateur.');
        }

        if ($user->isAdmin() && $data['role'] !== 'admin') {
            $this->ensureAnotherActiveAdminExists($user);
        }

        $actor = $request->user();
        $departmentName = $data['dept'] ?? $user->dept;

        DB::transaction(function () use ($actor, $data, $departmentName, $user): void {
            $user->update([
                'role' => $data['role'],
                'dept' => $departmentName,
                'role_assigned_by' => $actor->username,
                'role_assigned_at' => now(),
            ]);

            $portalDepartment = filled($departmentName)
                ? Department::query()->where('name', $departmentName)->whereIn('code', ['youth', 'ecodim'])->first()
                : null;

            MemberRoleAssignment::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->whereIn('slug', ['responsable_jeunesse', 'responsable_ecodim']))
                ->when($portalDepartment, fn ($query) => $query->where('scope_type', '!=', $portalDepartment->code))
                ->update(['status' => 'inactive', 'ends_at' => now()]);

            Membership::query()
                ->where('user_id', $user->id)
                ->whereIn('type', ['youth', 'ecodim'])
                ->when($portalDepartment, fn ($query) => $query->where('type', '!=', $portalDepartment->code))
                ->where('status', 'active')
                ->update(['status' => 'inactive', 'ends_at' => now()]);

            if ($data['role'] !== 'responsable' || ! $portalDepartment) {
                return;
            }

            Membership::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $portalDepartment->code,
                    'entity_id' => $portalDepartment->id,
                ],
                ['status' => 'active', 'starts_at' => now(), 'ends_at' => null],
            );

            $roleSlug = $portalDepartment->code === 'youth' ? 'responsable_jeunesse' : 'responsable_ecodim';
            $portalRole = Role::query()->firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => $portalDepartment->code === 'youth' ? 'Responsable jeunesse' : 'Responsable ECODIM', 'status' => 'active'],
            );

            MemberRoleAssignment::query()->create([
                'user_id' => $user->id,
                'role_id' => $portalRole->id,
                'scope_type' => $portalDepartment->code,
                'scope_id' => $portalDepartment->id,
                'status' => 'active',
                'starts_at' => now(),
                'assigned_by' => $actor->id,
            ]);
        });

        $user->notify(new RoleUpdated($user, $data['role'], $data['dept'] ?? $user->dept));

        return back()->with('success', 'Rôle mis à jour pour '.$user->full_name.'.');
    }

    /**
     * Activation / désactivation / remise en attente d'un compte.
     */
    public function setStatus(Request $request, User $user)
    {
        $this->ensurePrimaryAdminIsProtected($user);

        $data = $request->validate([
            'status' => ['required', 'in:pending,active,inactive'],
        ]);

        if ($user->isPrimaryAdmin() && $user->is(auth()->user()) && $data['status'] !== 'active') {
            abort(403, 'L’administrateur principal ne peut pas désactiver son propre compte.');
        }

        if ($user->isAdmin() && $user->status === 'active' && $data['status'] !== 'active') {
            $this->ensureAnotherActiveAdminExists($user);
        }

        $user->update(['status' => $data['status']]);

        return back()->with('success', 'Statut de '.$user->full_name.' : '.$data['status'].'.');
    }

    protected function ensurePrimaryAdminIsProtected(User $user): void
    {
        if ($user->isPrimaryAdmin() && auth()->id() !== $user->id) {
            throw new AuthorizationException('Seul l’administrateur principal peut modifier ce compte.');
        }
    }

    protected function ensureAnotherActiveAdminExists(User $user): void
    {
        $hasAnotherAdmin = User::query()
            ->where('role', 'admin')
            ->where('status', 'active')
            ->whereKeyNot($user->id)
            ->exists();

        abort_if(! $hasAnotherAdmin, 403, 'Le dernier administrateur actif doit rester en fonction.');
    }

    protected function validated(Request $request): array
    {
        $roles = auth()->user()->isAdmin()
            ? ['admin', 'secretariat', 'responsable', 'pasteur_n1', 'user']
            : ['responsable', 'pasteur_n1', 'user']; // le secrétariat ne crée pas d'admins

        return $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone', 'regex:/^\+?[0-9\s\-()]+$/'],
            'password' => ['required', 'min:8'],
            'role' => ['required', Rule::in($roles)],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
