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
use Illuminate\Database\Eloquent\Builder;
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

        if ($this->canGovernPortal('church')) {
            return ['church', 'youth', 'ecodim'];
        }

        if ($this->isChurchAdministrator()) {
            $sources[] = 'church';
        }

        if ($this->canAccessPortal('youth') && $this->portalRoleAssignments('youth')
            ->contains(fn (MemberRoleAssignment $assignment): bool => in_array(
                strtolower((string) ($assignment->role?->slug ?? '')),
                ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse'],
                true,
            ))) {
            $sources[] = 'youth';
        }

        if ($this->hasEcodimPermission('ecodim.events.manage')) {
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

        if ($this->canGovernPortal($this->currentPortal())) {
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

    public function canViewPortalInformation(string $portal): bool
    {
        return $this->status === 'active'
            && in_array(strtolower(trim($portal)), ['church', 'youth', 'ecodim'], true);
    }

    public function canGovernPortal(string $portal): bool
    {
        return $this->isPrimaryAdmin()
            && $this->status === 'active'
            && in_array(strtolower(trim($portal)), ['church', 'youth', 'ecodim'], true);
    }

    public function canUsePortal(string $portal, string $permission, EcodimClass|int|string|null $scope = null): bool
    {
        $portalKey = strtolower(trim($portal));

        if (! $this->canViewPortalInformation($portalKey)) {
            return false;
        }

        if ($portalKey === 'ecodim') {
            if ($scope instanceof EcodimClass) {
                return $this->hasEcodimClassPermission($scope, $permission);
            }

            if (is_int($scope)) {
                $class = EcodimClass::query()->find($scope);

                return $class !== null && $this->hasEcodimClassPermission($class, $permission);
            }

            if (is_string($scope)) {
                $class = EcodimClass::query()->where('name', $scope)->first();

                return $class !== null && $this->hasEcodimClassPermission($class, $permission);
            }

            return $this->hasEcodimPermission($permission);
        }

        return $this->hasPortalPermission($portalKey, $permission);
    }

    public function portalAccesses(): array
    {
        $accesses = [];

        if (! filled($this->member_id)) {
            return [];
        }

        $explicitMemberships = Membership::query()
            ->where('member_id', $this->member_id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->pluck('type')
            ->map(fn ($type) => strtolower((string) $type))
            ->all();

        if ($this->isChurchMember()) {
            $accesses[] = 'church';
        }

        $hasExplicitMembershipData = $explicitMemberships !== [];

        if (! $hasExplicitMembershipData && $this->isChurchMember()) {
            return ['church'];
        }

        foreach (['youth', 'ecodim'] as $portal) {
            if (in_array($portal, $explicitMemberships, true)) {
                $accesses[] = $portal;
            }
        }

        return array_values(array_unique($accesses));
    }

    public function primaryPortal(): string
    {
        if ($this->isChurchAdministrator() || $this->canGovernPortal('church')) {
            return 'church';
        }

        if ($this->hasPortalRole('ecodim', [
            'ecodim_manager',
            'ecodim_class_responsible',
            'responsable_ecodim',
            'animateur_ecodim',
            'enseignant_ecodim',
            'leader_ecodim',
        ]) && ! $this->isChurchAdministrator()) {
            return 'ecodim';
        }

        if ($this->belongsToPortal('ecodim') && ! $this->isChurchAdministrator()) {
            return 'ecodim';
        }

        if ($this->hasPortalRole('youth', [
            'responsable_jeunesse',
            'animateur_jeunesse',
            'leader_youth',
        ]) && ! $this->isChurchAdministrator()) {
            return 'youth';
        }

        if ($this->belongsToPortal('youth') && ! $this->isChurchAdministrator()) {
            return 'youth';
        }

        return $this->isChurchMember() ? 'youth' : 'church';
    }

    public function currentPortal(): string
    {
        $requestedPortal = request()->attributes->get('portal') ?? session('active_portal');

        if (is_string($requestedPortal) && ($this->canAccessPortal($requestedPortal)
                || $this->canViewPortalInformation($requestedPortal)
                || $this->canGovernPortal($requestedPortal))) {
            return $requestedPortal;
        }

        return $this->primaryPortal();
    }

    public function roleLabel(): string
    {
        if ($this->primaryPortal() === 'ecodim' && (
            $this->hasPortalRole('ecodim', ['ecodim_manager', 'responsable_ecodim', 'animateur_ecodim', 'enseignant_ecodim', 'leader_ecodim'])
            || ($this->isResponsable() && $this->belongsToPortal('ecodim'))
        )) {
            return 'Responsable ECODIM';
        }

        if ($this->primaryPortal() === 'youth' && (
            $this->hasPortalRole('youth', ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse'])
            || ($this->isResponsable() && $this->belongsToPortal('youth'))
        )) {
            return 'Responsable jeunesse';
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
        $portal = $this->portalNavigationKey();

        if (! $this->canGovernPortal($portal) && ! $this->hasOperationalPortalAccess($portal)) {
            return [
                ['route' => $this->portalDashboardRouteName($portal), 'label' => 'Accueil', 'icon' => '🏠'],
                ['route' => $this->portalAnnouncementsRouteName($portal), 'label' => 'Annonces', 'icon' => '📣'],
            ];
        }

        if ($portal === 'church') {
            return [
                ['route' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => '🏠'],
                ['route' => 'members.index', 'label' => 'Annuaire', 'icon' => '👥'],
                ['route' => 'attendances.pick', 'label' => 'Présences', 'icon' => '✅'],
                ['route' => 'events.index', 'label' => 'Événements', 'icon' => '📅'],
                ['route' => 'gallery.index', 'label' => 'Galerie', 'icon' => '🖼️'],
                ['route' => 'settings.index', 'label' => 'Paramètres', 'icon' => '⚙️'],
            ];
        }

        if ($portal === 'ecodim') {
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

    /** @return array<int, array{portal: string, label: string, route: string, access: string}> */
    public function informationalPortalDestinations(): array
    {
        if (! $this->canViewPortalInformation('church')) {
            return [];
        }

        return collect([
            'church' => 'Église',
            'youth' => 'Jeunesse',
            'ecodim' => 'ECODIM',
        ])->map(fn (string $label, string $portal): array => [
            'portal' => $portal,
            'label' => $label,
            'route' => $this->portalDashboardRouteName($portal),
            'access' => $this->portalAccessLevel($portal),
        ])->values()->all();
    }

    public function portalDashboardRouteName(string $portal): string
    {
        return match (strtolower(trim($portal))) {
            'church' => 'dashboard',
            'youth' => 'dashboard.youth',
            'ecodim' => 'dashboard.ecodim',
            default => 'dashboard',
        };
    }

    public function portalAnnouncementsRouteName(string $portal): string
    {
        return match (strtolower(trim($portal))) {
            'church' => 'portal.announcements.church',
            'youth' => 'portal.announcements.youth',
            'ecodim' => 'portal.announcements.ecodim',
            default => 'portal.announcements.church',
        };
    }

    private function portalAccessLevel(string $portal): string
    {
        if ($this->canGovernPortal($portal)) {
            return 'Gouvernance';
        }

        $hasOperationalAccess = match ($portal) {
            'church' => $this->isChurchAdministrator(),
            'youth' => $this->hasOperationalPortalAccess('youth'),
            'ecodim' => $this->hasEcodimPermission('ecodim.members.view')
                || $this->hasEcodimPermission('ecodim.classes.manage')
                || $this->hasEcodimPermission('ecodim.attendance.manage'),
            default => false,
        };

        return $hasOperationalAccess ? 'Gestion' : 'Consultation';
    }

    public function canAccessPortal(string $portal): bool
    {
        $requestedPortal = strtolower($portal);

        if ($requestedPortal === 'church') {
            if ($this->isChurchAdministrator()) {
                return true;
            }

            if (! filled($this->member_id)) {
                return false;
            }

            $activeMemberships = Membership::query()
                ->where('member_id', $this->member_id)
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->pluck('type')
                ->map(fn ($type) => strtolower((string) $type))
                ->all();

            return $activeMemberships !== [] && in_array('church', $activeMemberships, true);
        }

        if ($requestedPortal === 'ecodim') {
            if ($this->isChurchAdministrator()) {
                return false;
            }

            if (! filled($this->member_id)) {
                return false;
            }

            return $this->hasActiveEcodimMembership();
        }

        if ($requestedPortal === 'youth') {
            if ($this->isChurchAdministrator()) {
                return false;
            }

            if (filled($this->member_id) && Membership::query()
                ->where('member_id', $this->member_id)
                ->where('type', 'youth')
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->exists()) {
                return true;
            }

            return $this->hasPortalRole('youth', [
                'responsable_jeunesse',
                'animateur_jeunesse',
                'leader_youth',
            ]);
        }

        return false;
    }

    public function portalRoleAssignments(string $portal): Collection
    {
        $portalKey = strtolower($portal);

        return MemberRoleAssignment::query()
            ->where(function ($query): void {
                if (filled($this->member_id)) {
                    $query->where('member_id', $this->member_id);
                }

                $query->orWhere('user_id', $this->id);
            })
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(function ($query) use ($portalKey): void {
                $query->where('scope_type', $portalKey)
                    ->orWhere('scope_type', ucfirst($portalKey));
            })
            ->with('role')
            ->get();
    }

    public function hasOperationalPortalAccess(string $portal): bool
    {
        return match (strtolower(trim($portal))) {
            'church' => $this->isChurchAdministrator(),
            'youth' => $this->canAccessPortal('youth') && $this->portalRoleAssignments('youth')
                ->contains(fn (MemberRoleAssignment $assignment): bool => in_array(
                    strtolower((string) ($assignment->role?->slug ?? '')),
                    ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse'],
                    true,
                )),
            'ecodim' => $this->hasEcodimPermission('ecodim.members.view')
                || $this->hasEcodimPermission('ecodim.classes.manage')
                || $this->hasEcodimPermission('ecodim.attendance.manage'),
            default => false,
        };
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

        if ($portalKey === 'ecodim') {
            return $this->hasActiveEcodimMembership();
        }

        if (! filled($this->member_id)) {
            return $this->portalRoleAssignments($portalKey)->isNotEmpty();
        }

        return Membership::query()
            ->where('member_id', $this->member_id)
            ->where('type', $portalKey)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->exists()
            || $this->portalRoleAssignments($portalKey)->isNotEmpty();
    }

    public function canApproveYouthAccounts(): bool
    {
        return $this->canGovernPortal('youth')
            || $this->hasPortalRole('youth', 'responsable_jeunesse');
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
        if ($portal === 'ecodim') {
            return $this->hasEcodimPermission($permission);
        }

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
            $departmentIds = MemberRoleAssignment::query()
                ->where(function ($query): void {
                    if (filled($this->member_id)) {
                        $query->where('member_id', $this->member_id);
                    }

                    $query->orWhere('user_id', $this->id);
                })
                ->where('scope_type', 'department')
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->whereHas('role', fn ($query) => $query->where('slug', 'responsable'))
                ->pluck('scope_id');

            $departmentNames = Department::query()->whereIn('id', $departmentIds)->pluck('name')->all();

            if ($this->isResponsable() && filled($this->dept)) {
                $department = Department::query()->where('name', $this->dept)->first();

                if (! $department || ! in_array(strtolower((string) $department->code), ['ecodim', 'youth'], true)) {
                    $departmentNames[] = $this->dept;
                }
            }

            return Department::query()
                ->whereIn('name', array_values(array_unique(array_filter($departmentNames))))
                ->orderBy('name')
                ->get();
        }

        if ($portal !== 'youth') {
            return new Collection;
        }

        if ($this->canGovernPortal('youth')) {
            return Department::query()
                ->where(fn ($query) => $query->whereNull('code')->orWhere('code', 'youth'))
                ->orderBy('name')
                ->get();
        }

        $scopeIds = MemberRoleAssignment::query()
            ->where(function ($query): void {
                if (filled($this->member_id)) {
                    $query->where('member_id', $this->member_id);
                }

                $query->orWhere('user_id', $this->id);
            })
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
        if ($this->canGovernPortal('ecodim')) {
            return EcodimClass::query()->where('status', 'active')->orderBy('name')->get();
        }

        if (! $this->hasActiveEcodimMembership()) {
            return new Collection;
        }

        return EcodimClass::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(fn (EcodimClass $class): bool => $this->hasEcodimClassPermission($class, 'ecodim.attendance.manage'))
            ->values();
    }

    public function canManageAttendance(string $portal, ?string $department): bool
    {
        if ($portal === 'ecodim') {
            $class = filled($department)
                ? EcodimClass::query()->where('name', $department)->where('status', 'active')->first()
                : null;

            return $class !== null && $this->hasEcodimClassPermission($class, 'ecodim.attendance.manage');
        }

        if ($portal === 'youth' && $this->canGovernPortal('youth')) {
            return true;
        }

        return filled($department)
            && $this->attendanceDepartments($portal)->contains('name', $department);
    }

    public function hasActiveEcodimMembership(): bool
    {
        if (! filled($this->member_id)) {
            return false;
        }

        return Membership::query()
            ->where('member_id', $this->member_id)
            ->where('type', 'ecodim')
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->exists();
    }

    public function hasEcodimPermission(string $permission): bool
    {
        if (! filled($this->member_id) || ! $this->hasActiveEcodimMembership()) {
            return false;
        }

        $departmentId = Department::query()->where('code', 'ecodim')->value('id');

        if (! $departmentId) {
            return false;
        }

        return $this->ecodimPermissionAssignments($permission)
            ->where(function ($query) use ($departmentId): void {
                $query->where(fn ($query) => $query
                    ->where('scope_type', 'ecodim')
                    ->where('scope_id', $departmentId)
                    ->whereHas('role', fn ($roleQuery) => $roleQuery->where('slug', 'ecodim_manager')))
                    ->orWhere(fn ($query) => $query
                        ->where('scope_type', 'ecodim_class')
                        ->whereHas('role', fn ($roleQuery) => $roleQuery->where('slug', 'ecodim_class_responsible'))
                        ->whereExists(fn ($classQuery) => $classQuery
                            ->selectRaw('1')
                            ->from('ecodim_classes')
                            ->whereColumn('ecodim_classes.id', 'member_role_assignments.scope_id')
                            ->where('ecodim_classes.status', 'active')));
            })
            ->exists();
    }

    public function hasEcodimClassPermission(EcodimClass $class, string $permission): bool
    {
        if ($class->status !== 'active' || ! filled($this->member_id) || ! $this->hasActiveEcodimMembership()) {
            return false;
        }

        $departmentId = Department::query()->where('code', 'ecodim')->value('id');

        if (! $departmentId) {
            return false;
        }

        return $this->ecodimPermissionAssignments($permission)
            ->where(function ($query) use ($class, $departmentId): void {
                $query->where(fn ($query) => $query
                    ->where('scope_type', 'ecodim')
                    ->where('scope_id', $departmentId)
                    ->whereHas('role', fn ($roleQuery) => $roleQuery->where('slug', 'ecodim_manager')))
                    ->orWhere(fn ($query) => $query
                        ->where('scope_type', 'ecodim_class')
                        ->where('scope_id', $class->id)
                        ->whereHas('role', fn ($roleQuery) => $roleQuery->where('slug', 'ecodim_class_responsible')));
            })
            ->exists();
    }

    private function ecodimPermissionAssignments(string $permission): Builder
    {
        if (! filled($this->member_id)) {
            return MemberRoleAssignment::query()->whereRaw('1 = 0');
        }

        return MemberRoleAssignment::query()
            ->where('member_id', $this->member_id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->whereHas('role', fn ($query) => $query
                ->whereIn('slug', ['ecodim_manager', 'ecodim_class_responsible'])
                ->whereHas('permissions', fn ($permissionQuery) => $permissionQuery
                    ->where('permissions.slug', $permission)
                    ->where('permissions.status', 'active')));
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
