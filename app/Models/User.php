<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\PasswordResetRequested;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'username',
    'full_name',
    'sex',
    'email',
    'password',
    'phone',
    'role',
    'church_id',
    'member_id',
    'status',
    'dept',
    'birth_date',
    'address',
    'profile_photo_url',
    'created_by',
    'role_assigned_by',
    'role_assigned_at',
    'notes',
    'is_primary_admin',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'role_assigned_at' => 'datetime',
            'is_primary_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if ($user->isPrimaryAdmin()) {
                throw new AuthorizationException('L’administrateur principal ne peut pas être supprimé.');
            }
        });
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->isRole('admin');
    }

    public function isPastorPrincipal(): bool
    {
        return $this->isRole('pasteur_n1');
    }

    public function isChurchAdministrator(): bool
    {
        return $this->isAdmin() || $this->isSecretariat() || $this->isPastorPrincipal();
    }

    public function manageableContentSources(): array
    {
        $sources = [];

        if ($this->isAdmin()) {
            return ['church', 'youth', 'ecodim'];
        }

        if ($this->isChurchAdministrator()) {
            $sources[] = 'church';
        }

        if ($this->hasPortalRole('youth', ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse'])) {
            $sources[] = 'youth';
        }

        if ($this->hasPortalRole('ecodim', ['responsable_ecodim', 'animateur_ecodim', 'enseignant_ecodim'])) {
            $sources[] = 'ecodim';
        }

        return array_values(array_unique($sources));
    }

    public function canManageContentSource(string $source): bool
    {
        return in_array($source, $this->manageableContentSources(), true);
    }

    public function manageableContentSourcesForCurrentPortal(): array
    {
        $sources = $this->manageableContentSources();

        if ($this->isAdmin()) {
            return $sources;
        }

        $currentPortal = $this->currentPortal();
        $portalSources = array_values(array_filter($sources, fn (string $source): bool => $source === $currentPortal));

        if ($portalSources !== []) {
            return $portalSources;
        }

        return count($sources) === 1 && $sources[0] === 'ecodim' ? $sources : [];
    }

    public function isChurchMember(): bool
    {
        return $this->status === 'active';
    }

    public function portalAccesses(): array
    {
        $accesses = [];
        $explicitMemberships = Membership::query()
            ->where('user_id', $this->id)
            ->where('status', 'active')
            ->pluck('type')
            ->map(fn ($type) => strtolower((string) $type))
            ->all();

        if ($this->isChurchMember()) {
            $accesses[] = 'church';
        }

        $hasExplicitMembershipData = $explicitMemberships !== [];

        if (! $hasExplicitMembershipData && $this->isChurchMember()) {
            return ['church', 'youth', 'ecodim'];
        }

        foreach (['youth', 'ecodim'] as $portal) {
            if (in_array($portal, $explicitMemberships, true) || $this->hasPortalRole($portal, ['responsable_jeunesse', 'responsable_ecodim', 'animateur_jeunesse', 'animateur_ecodim', 'enseignant_ecodim', 'leader_youth', 'leader_ecodim'])) {
                $accesses[] = $portal;
            }
        }

        return array_values(array_unique($accesses));
    }

    public function primaryPortal(): string
    {
        if ($this->isChurchAdministrator()) {
            return 'church';
        }

        if ($this->hasPortalRole('ecodim', [
            'responsable_ecodim',
            'animateur_ecodim',
            'enseignant_ecodim',
            'leader_ecodim',
        ]) || $this->belongsToPortal('ecodim')) {
            return 'ecodim';
        }

        return $this->isChurchMember() ? 'youth' : 'church';
    }

    public function currentPortal(): string
    {
        $requestedPortal = request()->attributes->get('portal') ?? session('active_portal');

        return is_string($requestedPortal) && in_array($requestedPortal, $this->portalAccesses(), true)
            ? $requestedPortal
            : $this->primaryPortal();
    }

    public function roleLabel(): string
    {
        if ($this->hasPortalRole('youth', ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse'])) {
            return 'Responsable jeunesse';
        }

        if ($this->hasPortalRole('ecodim', ['responsable_ecodim', 'animateur_ecodim', 'enseignant_ecodim'])) {
            return 'Responsable ECODIM';
        }

        return match ($this->role) {
            'admin' => 'Administrateur',
            'pasteur_n1' => 'Pasteur principal',
            'secretariat' => 'Secrétariat',
            'responsable' => 'Responsable',
            'responsable_social' => 'Responsable social',
            default => 'Membre',
        };
    }

    public function dashboardRouteName(): string
    {
        return match ($this->primaryPortal()) {
            'church' => 'dashboard',
            'ecodim' => 'dashboard.ecodim',
            default => 'dashboard.youth',
        };
    }

    public function portalLabel(): string
    {
        return match ($this->currentPortal()) {
            'church' => 'Portail église',
            'ecodim' => 'Portail ECODIM',
            default => 'Portail jeunesse',
        };
    }

    public function portalNavigationKey(): string
    {
        return $this->currentPortal();
    }

    public function portalNavigationItems(): array
    {
        if ($this->portalNavigationKey() === 'church') {
            return [
                ['route' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => '🏠'],
                ['route' => 'members.index', 'label' => 'Annuaire', 'icon' => '👥'],
                ['route' => 'attendances.pick', 'label' => 'Présences', 'icon' => '✅'],
                ['route' => 'events.index', 'label' => 'Événements', 'icon' => '📅'],
                ['route' => 'gallery.index', 'label' => 'Galerie', 'icon' => '🖼️'],
                ['route' => 'settings.index', 'label' => 'Paramètres', 'icon' => '⚙️'],
            ];
        }

        if ($this->portalNavigationKey() === 'ecodim') {
            return [
                ['route' => 'dashboard.ecodim', 'label' => 'Portail ECODIM', 'icon' => '🏠'],
                ['route' => 'profile.edit', 'label' => 'Mon profil', 'icon' => '👤'],
                ['route' => 'members.index', 'label' => 'Annuaire', 'icon' => '👥'],
                ['route' => 'events.index', 'label' => 'Événements', 'icon' => '📅'],
                ['route' => 'gallery.index', 'label' => 'Galerie', 'icon' => '🖼️'],
            ];
        }

        $items = [
            ['route' => 'dashboard.youth', 'label' => 'Portail jeunesse', 'icon' => '🏠'],
            ['route' => 'profile.edit', 'label' => 'Mon profil', 'icon' => '👤'],
            ['route' => 'members.index', 'label' => 'Annuaire', 'icon' => '👥'],
            ['route' => 'youth.events.index', 'label' => 'Événements', 'icon' => '📅'],
            ['route' => 'gallery.index', 'label' => 'Galerie', 'icon' => '🖼️'],
            ['route' => 'social-visits.index', 'label' => 'Visites sociales', 'icon' => '🤝'],
        ];

        if ($this->attendanceDepartments('youth')->isNotEmpty()) {
            array_splice($items, 4, 0, [[
                'route' => 'youth.attendances.pick',
                'label' => 'Présences jeunesse',
                'icon' => '✅',
            ]]);
        }

        return $items;
    }

    public function canAccessPortal(string $portal): bool
    {
        $requestedPortal = strtolower($portal);

        if ($requestedPortal === 'church') {
            $activeMemberships = Membership::query()
                ->where('user_id', $this->id)
                ->where('status', 'active')
                ->pluck('type')
                ->map(fn ($type) => strtolower((string) $type))
                ->all();

            if ($activeMemberships !== [] && ! in_array('church', $activeMemberships, true)) {
                return false;
            }

            return $this->isChurchMember() || $this->isChurchAdministrator();
        }

        return in_array($requestedPortal, $this->portalAccesses(), true);
    }

    public function portalRoleAssignments(string $portal): Collection
    {
        $portalKey = strtolower($portal);

        return MemberRoleAssignment::query()
            ->where('user_id', $this->id)
            ->where('status', 'active')
            ->where(function ($query) use ($portalKey) {
                $query->where('scope_type', $portalKey)
                    ->orWhere('scope_type', ucfirst($portalKey));
            })
            ->with('role')
            ->get();
    }

    public function hasPortalRole(string $portal, array|string $roles): bool
    {
        $needed = collect((array) $roles)
            ->map(fn (string $role) => strtolower(trim($role)))
            ->all();

        if ($needed === []) {
            return false;
        }

        $legacyRole = strtolower((string) $this->role);
        if (in_array($legacyRole, $needed, true)) {
            return true;
        }

        return $this->portalRoleAssignments($portal)
            ->contains(fn (MemberRoleAssignment $assignment) => in_array(
                strtolower((string) ($assignment->role?->slug ?? $assignment->role?->name ?? '')),
                $needed,
                true,
            ));
    }

    public function belongsToPortal(string $portal): bool
    {
        $portalKey = strtolower($portal);
        $departmentName = Department::query()->where('code', $portalKey)->value('name');

        return Membership::query()
            ->where('user_id', $this->id)
            ->where('type', $portalKey)
            ->where('status', 'active')
            ->exists()
            || $this->portalRoleAssignments($portalKey)->isNotEmpty()
            || ($departmentName !== null && $this->dept === $departmentName);
    }

    public function canApproveYouthAccounts(): bool
    {
        return $this->hasPortalRole('youth', 'responsable_jeunesse');
    }

    public function portalPermissionsFor(string $portal): array
    {
        if ($portal === 'church') {
            return [
                'dashboard.view',
                'members.view',
                'attendance.manage',
                'events.manage',
                'settings.manage',
                'reports.view',
            ];
        }

        if ($portal === 'youth') {
            return [
                'profile.view',
                'events.view',
                'gallery.view',
                'social_visits.view',
                'activities.view',
                'youth.dashboard.view',
            ];
        }

        return [];
    }

    public function hasPortalPermission(string $portal, string $permission): bool
    {
        if ($portal === 'church') {
            return $this->isChurchAdministrator();
        }

        if ($portal === 'youth') {
            if (! $this->isChurchMember()) {
                return false;
            }

            $viewPermissions = ['profile.view', 'events.view', 'gallery.view', 'social_visits.view', 'activities.view', 'youth.dashboard.view'];

            if (in_array($permission, $viewPermissions, true)) {
                return true;
            }

            return in_array($permission, ['communications.manage', 'events.manage', 'activities.manage'], true)
                && $this->hasPortalRole('youth', ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse']);
        }

        return false;
    }

    public function attendanceDepartments(string $portal): Collection
    {
        if ($portal === 'church') {
            if ($this->isResponsable() && filled($this->dept)) {
                return Department::query()->where('name', $this->dept)->get();
            }

            $departmentIds = MemberRoleAssignment::query()
                ->where('user_id', $this->id)
                ->where('scope_type', 'department')
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->where('slug', 'responsable'))
                ->pluck('scope_id');

            return Department::query()->whereIn('id', $departmentIds)->orderBy('name')->get();
        }

        if ($portal !== 'youth') {
            return new Collection;
        }

        $scopeIds = MemberRoleAssignment::query()
            ->where('user_id', $this->id)
            ->where('scope_type', 'youth')
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('slug', [
                'responsable_jeunesse',
                'leader_youth',
                'animateur_jeunesse',
            ]))
            ->pluck('scope_id');
        $youthPortalDepartmentId = Department::query()->where('code', 'youth')->value('id');

        if ($youthPortalDepartmentId && $scopeIds->contains($youthPortalDepartmentId)) {
            return Department::query()
                ->where(fn ($query) => $query->whereNull('code')->orWhere('code', 'youth'))
                ->orderBy('name')
                ->get();
        }

        return Department::query()
            ->whereIn('id', $scopeIds)
            ->where(fn ($query) => $query->whereNull('code')->orWhere('code', 'youth'))
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, EcodimClass> */
    public function ecodimAttendanceClasses(): Collection
    {
        $classes = EcodimClass::query()->where('status', 'active');

        if ($this->isAdmin()) {
            return $classes->orderBy('name')->get();
        }

        $ecodimDepartmentId = Department::query()->where('code', 'ecodim')->value('id');
        $hasPortalWideAssignment = $ecodimDepartmentId && MemberRoleAssignment::query()
            ->where('user_id', $this->id)
            ->where('scope_type', 'ecodim')
            ->where('scope_id', $ecodimDepartmentId)
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('slug', [
                'responsable_ecodim',
                'animateur_ecodim',
                'enseignant_ecodim',
                'leader_ecodim',
            ]))
            ->exists();

        return $classes
            ->when(! $hasPortalWideAssignment, fn ($query) => $query->where('responsible_member_id', $this->id))
            ->orderBy('name')
            ->get();
    }

    public function canManageAttendance(string $portal, ?string $department): bool
    {
        if ($portal === 'ecodim') {
            return filled($department) && $this->ecodimAttendanceClasses()->contains('name', $department);
        }

        return filled($department)
            && $this->attendanceDepartments($portal)->contains('name', $department);
    }

    /** @return array<string, string> */
    public function departmentActions(): array
    {
        if ($this->hasPortalRole('youth', ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse'])) {
            return [
                'communications.manage' => 'Publier les communications jeunesse',
                'events.manage' => 'Gérer les activités jeunesse',
                'members.view' => 'Consulter les membres jeunesse',
            ];
        }

        if ($this->hasPortalRole('ecodim', ['responsable_ecodim', 'animateur_ecodim', 'enseignant_ecodim'])) {
            return [
                'communications.manage' => 'Publier les communications ECODIM',
                'ecodim.classes.manage' => 'Gérer les classes ECODIM',
                'ecodim.transitions.manage' => 'Suivre les transitions ECODIM',
            ];
        }

        if ($this->isChurchAdministrator() && $this->currentPortal() === 'church') {
            return [
                'events.manage' => 'Gérer les événements de l’église',
                'communications.manage' => 'Publier les communications de l’église',
                'departments.manage' => 'Administrer les départements',
            ];
        }

        return [];
    }

    public function isPrimaryAdmin(): bool
    {
        return (bool) $this->is_primary_admin;
    }

    public function isSecretariat(): bool
    {
        return $this->isRole('secretariat');
    }

    public function isResponsable(): bool
    {
        return $this->isRole('responsable');
    }

    /**
     * Profils habilités à gérer les médias de la galerie et du direct.
     */
    public function managesMedia(): bool
    {
        if ($this->isAdmin() || $this->isSecretariat()) {
            return true;
        }

        return ($this->isResponsable() && in_array($this->dept, ['Médias/DCC', 'Médias', 'DCC', 'DCC Église', 'DCC Jeunesse'], true))
            || $this->hasPortalRole('youth', ['responsable_dcc_jeunesse', 'dcc_jeunesse'])
            || $this->hasPortalRole('ecodim', ['responsable_dcc_ecodim', 'dcc_ecodim']);
    }

    public function canManageVideoArchives(): bool
    {
        return $this->managesMedia();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function fcmTokens()
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetRequested($token));
    }

    public function isSocialResponsable(): bool
    {
        return $this->isResponsable() && $this->dept === 'Social';
    }
}
