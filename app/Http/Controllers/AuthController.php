<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\MemberMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Connexion par email OU par nom d'utilisateur.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $password = $credentials['password'];
        $user = $this->findUserByLogin($login);

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withErrors(['login' => 'Identifiants incorrects.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('active_portal', $user->primaryPortal());

        return redirect()->intended(route($user->dashboardRouteName()));
    }

    public function showRegister()
    {
        return view('auth.register', ['departments' => Department::orderBy('name')->get()]);
    }

    /**
     * Auto-inscription : compte créé avec le statut 'pending',
     * à valider par l'administrateur.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'full_name' => ['required', 'string', 'max:150'],
            'sex' => ['required', 'in:male,female'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone', 'regex:/^\+?[0-9\s\-()]+$/'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $match = app(MemberMatchingService::class)->matchForRegistration([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);

        if ($match['ambiguous'] ?? false) {
            return back()->withErrors(['full_name' => 'Une personne similaire existe déjà. Merci de contacter l’administration pour validation humaine.'])->withInput();
        }

        $member = $match['member'] ?? Member::query()->firstOrCreate(
            ['email' => strtolower(trim($data['email']))],
            [
                'name' => trim($data['full_name']),
                'first_name' => Member::splitFullName($data['full_name'])[0],
                'last_name' => Member::splitFullName($data['full_name'])[1],
                'sex' => $data['sex'],
                'phone' => $this->normalizePhone($data['phone']),
                'birth_date' => $data['birth_date'] ?? null,
                'email' => strtolower(trim($data['email'])),
                'role' => 'user',
            ],
        );

        $user = User::query()->firstOrCreate(
            ['email' => strtolower(trim($data['email']))],
            [
                'username' => $data['username'],
                'full_name' => $data['full_name'],
                'member_id' => $member->id,
                'sex' => $data['sex'],
                'phone' => $this->normalizePhone($data['phone']),
                'password' => Hash::make($data['password']),
                'role' => 'user',
                'status' => 'pending',
                'created_by' => 'auto-inscription',
                'birth_date' => $data['birth_date'] ?? null,
            ],
        );

        if (filled($user->member_id) && $user->member_id !== $member->id) {
            $user->member_id = $member->id;
            $user->save();
        }

        Membership::query()->firstOrCreate(
            ['member_id' => $member->id, 'type' => 'church'],
            ['entity_id' => 1, 'status' => 'pending', 'starts_at' => now()],
        );

        return redirect()->route('login')
            ->with('success', 'Compte créé ! Il est en attente de validation par l\'administrateur.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Écran d'attente pour les comptes 'pending' ou 'inactive'.
     */
    public function pending()
    {
        if (Auth::user()->status === 'active') {
            return redirect()->route('dashboard');
        }

        return view('auth.pending');
    }

    public function profile()
    {
        return view('profile.edit', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function updateProfile(Request $request, CloudinaryService $cloudinary)
    {
        $user = Auth::user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]+$/'],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($user->isAdmin()) {
            $data['dept'] = $data['dept'] ?? $user->dept;
        } else {
            $data['dept'] = $user->dept;
        }

        $user->full_name = $data['full_name'];
        $user->phone = filled($data['phone'] ?? null) ? $this->normalizePhone($data['phone']) : $user->phone;
        $user->dept = $data['dept'];
        $user->birth_date = $data['birth_date'] ?? $user->birth_date;
        $user->address = $data['address'] ?? $user->address;

        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_photo')) {
            try {
                $uploaded = $cloudinary->upload($request->file('profile_photo'), 'appjeune-kzi/profiles');
                $user->profile_photo_url = $uploaded['url'];
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['profile_photo' => $e->getMessage()])->withInput();
            }
        }

        $user->save();

        return back()->with('success', 'Profil mis à jour.');
    }

    protected function findUserByLogin(string $login): ?User
    {
        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user) {
            return $user;
        }

        $normalizedLogin = $this->normalizePhone($login);

        if ($normalizedLogin === '') {
            return null;
        }

        return User::query()
            ->whereNotNull('phone')
            ->get()
            ->first(fn (User $candidate) => $this->normalizePhone((string) $candidate->phone) === $normalizedLogin);
    }

    protected function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        $digits = preg_replace('/^00/', '', $digits);

        if (str_starts_with($digits, '243')) {
            $digits = '0'.substr($digits, 3);
        }

        return $digits;
    }
}
