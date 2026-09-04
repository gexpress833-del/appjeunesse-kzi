<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\CloudinaryService;
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

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Identifiants incorrects.'])->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
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
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        User::create([
            ...$data,
            'role' => 'user',
            'status' => 'pending',
            'created_by' => 'auto-inscription',
        ]);

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
            'phone' => ['nullable', 'string', 'max:30'],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $user->full_name = $data['full_name'];
        $user->phone = $data['phone'] ?? $user->phone;
        $user->dept = $data['dept'] ?? $user->dept;
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
}
