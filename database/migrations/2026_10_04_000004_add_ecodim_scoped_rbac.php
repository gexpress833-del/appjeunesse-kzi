<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionNames = [
            'ecodim.classes.manage' => 'Gérer les classes ECODIM',
            'ecodim.attendance.manage' => 'Gérer les présences ECODIM',
            'ecodim.events.manage' => 'Gérer les événements ECODIM',
            'ecodim.members.view' => 'Consulter les membres ECODIM',
        ];

        foreach ($permissionNames as $slug => $name) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'status' => 'active', 'updated_at' => now(), 'created_at' => now()],
            );
        }

        $rolePermissions = [
            'ecodim_manager' => array_keys($permissionNames),
            'ecodim_class_responsible' => [
                'ecodim.classes.manage',
                'ecodim.attendance.manage',
                'ecodim.events.manage',
                'ecodim.members.view',
            ],
        ];

        foreach ($rolePermissions as $roleSlug => $permissionSlugs) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $roleSlug],
                ['name' => $roleSlug === 'ecodim_manager' ? 'Responsable ECODIM global' : 'Responsable de classe ECODIM', 'status' => 'active', 'updated_at' => now(), 'created_at' => now()],
            );

            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

            foreach ($permissionSlugs as $permissionSlug) {
                $permissionId = DB::table('permissions')->where('slug', $permissionSlug)->value('id');

                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            }
        }

        $departmentId = DB::table('departments')->where('code', 'ecodim')->value('id');
        $legacyRoleId = DB::table('roles')->where('slug', 'responsable_ecodim')->value('id');
        $managerRoleId = DB::table('roles')->where('slug', 'ecodim_manager')->value('id');

        if ($departmentId && $legacyRoleId && $managerRoleId) {
            DB::table('member_role_assignments')
                ->where('role_id', $legacyRoleId)
                ->where('scope_type', 'ecodim')
                ->where('scope_id', $departmentId)
                ->whereNotNull('member_id')
                ->update(['role_id' => $managerRoleId]);
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('slug', ['ecodim_manager', 'ecodim_class_responsible'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', [
            'ecodim.classes.manage',
            'ecodim.attendance.manage',
            'ecodim.events.manage',
            'ecodim.members.view',
        ])->pluck('id');

        DB::table('role_permissions')->whereIn('role_id', $roleIds)->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->whereNotIn('id', function ($query): void {
            $query->select('permission_id')->from('role_permissions');
        })->delete();
        DB::table('roles')->whereIn('id', $roleIds)->whereNotIn('id', function ($query): void {
            $query->select('role_id')->from('member_role_assignments');
        })->delete();
    }
};
