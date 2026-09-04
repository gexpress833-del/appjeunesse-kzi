<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Liste complète des comptes — admin uniquement.
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderBy('full_name')
            ->paginate(25)->withQueryString();

        return view('users.index', [
            'users' => $users,
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['status', 'role']),
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

        User::create($data);

        return redirect()->route(auth()->user()->isAdmin() ? 'users.index' : 'dashboard')
            ->with('success', 'Compte créé pour '.$data['full_name'].' — en attente de validation par l\'administrateur.');
    }

    /**
     * Validation d'un compte en attente : pending -> active.
     */
    public function validateAccount(User $user)
    {
        $user->update([
            'status' => 'active',
            'role_assigned_by' => auth()->user()->username,
            'role_assigned_at' => now(),
        ]);

        return back()->with('success', 'Compte de '.$user->full_name.' validé.');
    }

    /**
     * Attribution du rôle (et du département supervisé pour un responsable).
     */
    public function assignRole(Request $request, User $user)
    {
        $this->ensurePrimaryAdminIsProtected($user);

        $data = $request->validate([
            'role' => ['required', 'in:admin,secretariat,responsable,user'],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
        ]);

        $user->update([
            'role' => $data['role'],
            'dept' => $data['dept'] ?? $user->dept,
            'role_assigned_by' => auth()->user()->username,
            'role_assigned_at' => now(),
        ]);

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

        $user->update(['status' => $data['status']]);

        return back()->with('success', 'Statut de '.$user->full_name.' : '.$data['status'].'.');
    }

    protected function ensurePrimaryAdminIsProtected(User $user): void
    {
        if ($user->isPrimaryAdmin() && auth()->id() !== $user->id) {
            throw new AuthorizationException('Seul l’administrateur principal peut modifier ce compte.');
        }
    }

    protected function validated(Request $request): array
    {
        $roles = auth()->user()->isAdmin()
            ? ['admin', 'secretariat', 'responsable', 'user']
            : ['responsable', 'user']; // le secrétariat ne crée pas d'admins

        return $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'min:8'],
            'role' => ['required', Rule::in($roles)],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
