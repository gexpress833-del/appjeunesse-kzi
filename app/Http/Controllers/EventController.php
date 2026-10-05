<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EcodimClassMember;
use App\Models\Event;
use App\Models\MemberRoleAssignment;
use App\Models\User;
use App\Notifications\EventCreated;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Liste des événements : à venir d'abord, puis passés.
     */
    public function index()
    {
        $user = auth()->user();
        $portal = $user->currentPortal();
        $ecodimClasses = $portal === 'ecodim' ? $user->ecodimAttendanceClasses() : collect();

        return view('events.index', [
            'portal' => $portal,
            'ecodimClasses' => $ecodimClasses,
            'upcoming' => Event::query()
                ->where('portal', $portal)
                ->when($portal === 'ecodim', fn ($query) => $query->whereIn('dept', $ecodimClasses->pluck('name')))
                ->when($user->isResponsable(), fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhere('dept', $user->dept)))
                ->upcoming()
                ->withCount('members')
                ->paginate(10, ['*'], 'up')
                ->withQueryString(),
            'past' => Event::query()
                ->where('portal', $portal)
                ->when($portal === 'ecodim', fn ($query) => $query->whereIn('dept', $ecodimClasses->pluck('name')))
                ->when($user->isResponsable(), fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhere('dept', $user->dept)))
                ->past()
                ->withCount('members')
                ->paginate(10, ['*'], 'past')
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $portal = $user->currentPortal();
        $ecodimClasses = collect();

        if ($portal === 'youth') {
            $departments = $user->attendanceDepartments('youth');
            $department = $departments->firstWhere('name', $user->dept)
                ?? $departments->firstWhere('code', 'youth')
                ?? $departments->first();

            abort_unless($department && $user->canManageAttendance('youth', $department->name), 403, 'Une affectation active de responsable jeunesse est requise.');
            $departmentName = $department->name;
        } elseif ($portal === 'ecodim') {
            $ecodimClasses = $user->ecodimAttendanceClasses();
            abort_if($ecodimClasses->isEmpty(), 403, 'Une affectation active à une classe ECODIM est requise.');
            $departmentName = $ecodimClasses->count() === 1 ? $ecodimClasses->first()->name : null;
        } else {
            abort_if($user->isResponsable() && blank($user->dept), 403, 'Votre compte responsable doit être rattaché à un département.');
            $departmentName = $user->isResponsable() ? $user->dept : null;
        }

        return view('events.form', [
            'event' => new Event([
                'date' => now()->next('sunday')->setTime(9, 0),
                'dept' => $departmentName,
                'portal' => $portal,
            ]),
            'ecodimClasses' => $ecodimClasses,
        ]);
    }

    public function store(Request $request, CloudinaryService $cloudinary)
    {
        $user = auth()->user();
        $portal = $request->attributes->get('portal', 'church');
        $data = $this->validated($request, $portal);

        if ($portal === 'youth') {
            $departments = $user->attendanceDepartments('youth');
            $department = $departments->firstWhere('name', $user->dept)
                ?? $departments->firstWhere('code', 'youth')
                ?? $departments->first();

            abort_unless($department && $user->canManageAttendance('youth', $department->name), 403, 'Une affectation active de responsable jeunesse est requise.');
            $data['dept'] = $department->name;
        } elseif ($portal === 'ecodim') {
            $ecodimClasses = $user->ecodimAttendanceClasses();
            $class = $ecodimClasses->firstWhere('name', $data['dept'])
                ?? ($ecodimClasses->count() === 1 ? $ecodimClasses->first() : null);

            abort_unless($class, 403, 'Vous ne pouvez créer un événement que pour une classe ECODIM qui vous est affectée.');
            $data['dept'] = $class->name;
        } elseif ($user->isResponsable()) {
            abort_if(blank($user->dept), 403, 'Votre compte responsable doit être rattaché à un département.');
            $data['dept'] = $user->dept;
        }

        $data['portal'] = $portal;
        $data['name'] = $this->normalizeEventName($data['name']);
        $data['created_by'] = $user->username;
        $data = $this->handlePhoto($request, $cloudinary, $data);

        $event = Event::create($data);

        if ($portal === 'ecodim') {
            $ecodimDepartmentId = Department::query()->where('code', 'ecodim')->value('id');
            $globalResponsibleIds = MemberRoleAssignment::query()
                ->where('scope_type', 'ecodim')
                ->where('scope_id', $ecodimDepartmentId)
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->whereIn('slug', ['ecodim_manager', 'responsable_ecodim', 'animateur_ecodim', 'enseignant_ecodim', 'leader_ecodim']))
                ->pluck('user_id');
            $classResponsibleId = $class->responsible_member_id;
            $classResponsibleUserIds = User::query()
                ->where('member_id', $classResponsibleId)
                ->where('status', 'active')
                ->pluck('id');
            $eventOwners = User::query()
                ->whereIn('id', $globalResponsibleIds->concat($classResponsibleUserIds)->filter()->unique())
                ->where('status', 'active')
                ->get()
                ->reject(fn (User $owner): bool => $owner->is($user));

            foreach ($eventOwners as $owner) {
                $owner->notify(new EventCreated($event, $user));
            }

            $classMemberIds = EcodimClassMember::query()
                ->where('class_id', $class->id)
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->pluck('member_id');

            User::query()
                ->whereIn('member_id', $classMemberIds)
                ->where('status', 'active')
                ->whereKeyNot($user->id)
                ->get()
                ->each(fn (User $recipient) => $recipient->notify(new EventCreated($event, $user)));
        } else {
            $eventOwners = $portal === 'youth'
            ? User::query()->whereIn('id', MemberRoleAssignment::query()
                ->where('scope_type', 'youth')
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->whereIn('slug', ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse']))
                ->pluck('user_id'))->get()
            : User::query()
                ->whereIn('role', ['admin', 'secretariat', 'pasteur_n1', 'responsable'])
                ->when($event->dept, fn ($query) => $query->where(function ($departmentQuery) use ($event) {
                    $departmentQuery->where('role', 'admin')
                        ->orWhere('role', 'secretariat')
                        ->orWhere('role', 'pasteur_n1')
                        ->orWhere(fn ($responsableQuery) => $responsableQuery->where('role', 'responsable')->where('dept', $event->dept));
                }))
                ->get();

            foreach ($eventOwners as $owner) {
                $owner->notify(new EventCreated($event, $user));
            }

            User::query()
                ->where('role', 'user')
                ->where('status', 'active')
                ->get()
                ->each(fn (User $recipient) => $recipient->notify(new EventCreated($event, $user)));
        }

        $indexRoute = match ($portal) {
            'youth' => 'youth.events.index',
            'ecodim' => 'ecodim.events.index',
            default => 'events.index',
        };

        return redirect()->route($indexRoute)->with('success', 'Événement créé.');
    }

    public function edit(Event $event)
    {
        $this->authorizeDepartment($event);

        return view('events.form', ['event' => $event]);
    }

    public function update(Request $request, Event $event, CloudinaryService $cloudinary)
    {
        $this->authorizeDepartment($event);

        $data = $this->validated($request);

        if (auth()->user()->isResponsable()) {
            $data['dept'] = $event->dept;
        }

        if ($request->hasFile('photo')) {
            $cloudinary->delete($event->cloudinary_public_id);
            $uploaded = $cloudinary->upload($request->file('photo'), 'appjeune-kzi/events');
            $data['photo_url'] = $uploaded['url'];
            $data['cloudinary_public_id'] = $uploaded['public_id'];
        }

        $data['name'] = $this->normalizeEventName($data['name']);
        $event->update($data);
        $event->photos()->update(['event_name' => $event->name]);

        return redirect()->route('events.index')->with('success', 'Événement mis à jour.');
    }

    public function destroy(Event $event, CloudinaryService $cloudinary)
    {
        $this->authorizeDepartment($event);

        $cloudinary->delete($event->cloudinary_public_id);
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Événement supprimé.');
    }

    protected function validated(Request $request, ?string $portal = null): array
    {
        $departmentRule = $portal === 'ecodim'
            ? ['nullable', 'string', 'exists:ecodim_classes,name']
            : ['nullable', 'string', 'exists:departments,name'];

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'dept' => $departmentRule,
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);
    }

    protected function normalizeEventName(string $name): string
    {
        $normalizedName = Str::of($name)->squish()->lower()->toString();

        return Str::ucfirst($normalizedName);
    }

    protected function handlePhoto(Request $request, CloudinaryService $cloudinary, array $data): array
    {
        if (! $request->hasFile('photo')) {
            return $data;
        }

        $uploaded = $cloudinary->upload($request->file('photo'), 'appjeune-kzi/events');
        $data['photo_url'] = $uploaded['url'];
        $data['cloudinary_public_id'] = $uploaded['public_id'];

        return $data;
    }

    protected function authorizeDepartment(Event $event): void
    {
        $user = auth()->user();

        abort_unless($event->portal === $user->currentPortal(), 403, 'Cet événement appartient à un autre portail.');

        if ($event->portal === 'ecodim') {
            abort_unless($user->canManageAttendance('ecodim', $event->dept), 403, 'Cette classe ECODIM ne vous est pas affectée.');

            return;
        }

        if ($user->isResponsable() && $event->dept !== $user->dept) {
            abort(403, 'Vous ne pouvez gérer que les événements de votre département.');
        }
    }
}
