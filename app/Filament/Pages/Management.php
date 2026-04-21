<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Management extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'User Management';
    protected static string $view = 'filament.pages.management';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Admin & Manager bypass
        if ($user->role == 'admin') {
            return true;
        }

        $role = Role::where('name', $user->role)->first();
        $role_id = $role->id ?? null;
        
        if (!$role_id) {
            return false;
        }
        
        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role_id)
            ->pluck('permission_id');

        if ($rolePermissionIds->isEmpty()) {
            return false;
        }
        
        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();

        if (!in_array('management', $permissionResources)) {
            return false;
        }

        return true;
    }

    /* ================= PERMISSION ================= */
    public $permission_name;
    public $resource;
    public $editingPermissionId = null;

    /* ================= ROLE ================= */
    public $role_name;
    public $selectedRole;
    public array $selectedPermissions = [];
    public $editingRoleId = null;

    /* ================= USER ================= */
    public $user_name;
    public $user_email;
    public $user_password;
    public $user_role;
    public $editingUserId = null;
    public $showUserForm = false;

    /* ================= TABLES ================= */
    public $showPermissionsTable = false;
    public $showRolesTable = false;
    public $showUsersTable = true;

    /* ---------- PERMISSION METHODS ---------- */
    public function createPermission()
    {
        $this->validate([
            'permission_name' => 'required',
            'resource' => 'required',
        ]);

        Permission::create([
            'name' => $this->permission_name,
            'resource' => $this->resource,
        ]);

        $this->reset(['permission_name', 'resource']);
        $this->showPermissionsTable = true;

        Notification::make()
            ->title('Permission Created')
            ->success()
            ->send();
    }

    public function editPermission($id)
    {
        $permission = Permission::find($id);
        $this->editingPermissionId = $id;
        $this->permission_name = $permission->name;
        $this->resource = $permission->resource;
    }

    public function updatePermission()
    {
        $this->validate([
            'permission_name' => 'required',
            'resource' => 'required',
        ]);

        $permission = Permission::find($this->editingPermissionId);
        $permission->update([
            'name' => $this->permission_name,
            'resource' => $this->resource,
        ]);

        $this->reset(['permission_name', 'resource', 'editingPermissionId']);

        Notification::make()
            ->title('Permission Updated')
            ->success()
            ->send();
    }

    public function deletePermission($id)
    {
        Permission::find($id)->delete();
        
        Notification::make()
            ->title('Permission Deleted')
            ->success()
            ->send();
    }

    public function cancelPermissionEdit()
    {
        $this->reset(['permission_name', 'resource', 'editingPermissionId']);
    }

    /* ---------- ROLE METHODS ---------- */
    public function createRole()
    {
        $this->validate([
            'role_name' => 'required|unique:roles,name',
        ]);

        Role::create([
            'name' => $this->role_name
        ]);

        $this->reset('role_name');
        $this->showRolesTable = true;

        Notification::make()
            ->title('Role Created')
            ->success()
            ->send();
    }

    public function editRole($id)
    {
        $role = Role::find($id);
        $this->editingRoleId = $id;
        $this->role_name = $role->name;
    }

    public function updateRole()
    {
        $this->validate([
            'role_name' => 'required|unique:roles,name,' . $this->editingRoleId,
        ]);

        $role = Role::find($this->editingRoleId);
        $role->update([
            'name' => $this->role_name,
        ]);

        $this->reset(['role_name', 'editingRoleId']);

        Notification::make()
            ->title('Role Updated')
            ->success()
            ->send();
    }

    public function deleteRole($id)
    {
        Role::find($id)->delete();
        
        Notification::make()
            ->title('Role Deleted')
            ->success()
            ->send();
    }

    public function cancelRoleEdit()
    {
        $this->reset(['role_name', 'editingRoleId']);
    }

    /* ---------- PERMISSION ASSIGNMENT METHODS ---------- */
    public function loadPermissions()
    {
        $role = Role::find($this->selectedRole);
        $this->selectedPermissions = $role
            ? $role->permissions->pluck('id')->toArray()
            : [];
    }

    public function saveRolePermissions()
    {
        $role = Role::find($this->selectedRole);
        $role->permissions()->sync($this->selectedPermissions);

        Notification::make()
            ->title('Permissions Updated')
            ->success()
            ->send();
    }

    /* ---------- USER METHODS ---------- */
    public function createUser()
    {
        $this->validate([
            'user_name' => 'required',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|min:6',
            'user_role' => 'required',
        ]);

        User::create([
            'name'     => $this->user_name,
            'email'    => $this->user_email,
            'password' => Hash::make($this->user_password),
            'role'     => $this->user_role,
        ]);

        $this->reset(['user_name', 'user_email', 'user_password', 'user_role', 'showUserForm']);

        Notification::make()
            ->title('User Created Successfully')
            ->success()
            ->send();
    }

    public function editUser($id)
    {
        $user = User::find($id);
        $this->editingUserId = $id;
        $this->user_name = $user->name;
        $this->user_email = $user->email;
        $this->user_role = $user->role;
        $this->user_password = '';
        $this->showUserForm = true;
    }

    public function updateUser()
    {
        $this->validate([
            'user_name' => 'required',
            'user_email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'user_role' => 'required',
        ]);

        $user = User::find($this->editingUserId);
        $userData = [
            'name' => $this->user_name,
            'email' => $this->user_email,
            'role' => $this->user_role,
        ];

        if ($this->user_password) {
            $userData['password'] = Hash::make($this->user_password);
        }

        $user->update($userData);

        $this->reset(['user_name', 'user_email', 'user_password', 'user_role', 'editingUserId', 'showUserForm']);

        Notification::make()
            ->title('User Updated Successfully')
            ->success()
            ->send();
    }

    public function deleteUser($id)
    {
        User::find($id)->delete();
        
        Notification::make()
            ->title('User Deleted')
            ->success()
            ->send();
    }

    public function cancelUserEdit()
    {
        $this->reset(['user_name', 'user_email', 'user_password', 'user_role', 'editingUserId', 'showUserForm']);
    }

    public function showCreateUserForm()
    {
        $this->reset(['user_name', 'user_email', 'user_password', 'user_role', 'editingUserId']);
        $this->showUserForm = true;
    }

    /* ---------- DATA LOADERS ---------- */
    public function getRolesProperty()
    {
        return Role::all();
    }

    public function getPermissionsProperty()
    {
        return Permission::all();
    }

    public function getNonAdminUsersProperty()
    {
        return User::where('role', '!=', 'admin')
                   ->orWhereNull('role')
                   ->orderBy('created_at', 'desc')
                   ->get();
    }
}   