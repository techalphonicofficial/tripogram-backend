<x-filament::page>

    {{-- PERMISSION --}}
    <x-filament::card>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">
                @if($editingPermissionId)
                    Edit Permission
                @else
                    Create Permission
                @endif
            </h2>
            <button wire:click="$set('showPermissionsTable', !$showPermissionsTable)" 
                    class="text-primary-600 hover:text-primary-800">
                {{ $showPermissionsTable ? 'Hide' : 'Show' }} Permissions List
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <input wire:model="permission_name" class="border rounded p-2" placeholder="Permission Name">
            <input wire:model="resource" class="border rounded p-2" placeholder="Resource">

            <div class="col-span-2 flex gap-2">
                @if($editingPermissionId)
                    <button wire:click="updatePermission"
                        class="bg-green-600 text-white px-4 py-2 rounded">
                        Update Permission
                    </button>
                    <button wire:click="cancelPermissionEdit"
                        class="bg-gray-500 text-white px-4 py-2 rounded">
                        Cancel
                    </button>
                @else
                    <button wire:click="createPermission"
                        class="bg-primary-600 text-white px-4 py-2 rounded">
                        Add Permission
                    </button>
                @endif
            </div>
        </div>

        {{-- Permissions Table --}}
        @if($showPermissionsTable)
            <div class="mt-6">
                <h3 class="font-semibold mb-2">All Permissions</h3>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-left">Name</th>
                            <th class="border p-2 text-left">Resource</th>
                            <th class="border p-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->permissions as $permission)
                            <tr>
                                <td class="border p-2">{{ $permission->name }}</td>
                                <td class="border p-2">{{ $permission->resource }}</td>
                                <td class="border p-2">
                                    <button wire:click="editPermission({{ $permission->id }})" 
                                            class="text-blue-600 hover:text-blue-800 mr-2">
                                        Edit
                                    </button>
                                    <button wire:click="deletePermission({{ $permission->id }})" 
                                            wire:confirm="Are you sure you want to delete this permission?"
                                            class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::card>

    {{-- ROLE --}}
    <x-filament::card class="mt-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">
                @if($editingRoleId)
                    Edit Role
                @else
                    Create Role
                @endif
            </h2>
            <button wire:click="$set('showRolesTable', !$showRolesTable)" 
                    class="text-primary-600 hover:text-primary-800">
                {{ $showRolesTable ? 'Hide' : 'Show' }} Roles List
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <input wire:model="role_name" class="border rounded p-2" placeholder="Role Name">

            <div class="flex gap-2">
                @if($editingRoleId)
                    <button wire:click="updateRole"
                        class="bg-green-600 text-white px-4 py-2 rounded">
                        Update Role
                    </button>
                    <button wire:click="cancelRoleEdit"
                        class="bg-gray-500 text-white px-4 py-2 rounded">
                        Cancel
                    </button>
                @else
                    <button wire:click="createRole"
                        class="bg-primary-600 text-white px-4 py-2 rounded">
                        Create Role
                    </button>
                @endif
            </div>
        </div>

        {{-- Roles Table --}}
        @if($showRolesTable)
            <div class="mt-6">
                <h3 class="font-semibold mb-2">All Roles</h3>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-left">Name</th>
                            <th class="border p-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->roles as $role)
                            <tr>
                                <td class="border p-2">{{ $role->name }}</td>
                                <td class="border p-2">
                                    <button wire:click="editRole({{ $role->id }})" 
                                            class="text-blue-600 hover:text-blue-800 mr-2">
                                        Edit
                                    </button>
                                    <button wire:click="deleteRole({{ $role->id }})" 
                                            wire:confirm="Are you sure you want to delete this role?"
                                            class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::card>

    {{-- ASSIGN PERMISSIONS --}}
    <x-filament::card class="mt-6">
        <h2 class="text-lg font-bold mb-4">Assign Permissions to Role</h2>

        <select wire:model="selectedRole"
                wire:change="loadPermissions"
                class="border rounded p-2 w-full">
            <option value="">Select Role</option>
            @foreach($this->roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>

        @if($selectedRole)
            <div class="grid grid-cols-3 gap-2 mt-4">
                @foreach($this->permissions as $permission)
                    <label class="flex items-center gap-2">
                        <input type="checkbox"
                               wire:model="selectedPermissions"
                               value="{{ $permission->id }}">
                        {{ $permission->name }}
                    </label>
                @endforeach
            </div>

            <button wire:click="saveRolePermissions"
                class="mt-4 bg-primary-600 text-white px-4 py-2 rounded">
                Save Permissions
            </button>
        @endif
    </x-filament::card>

    {{-- USER --}}
    <x-filament::card class="mt-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">
                @if($editingUserId)
                    Edit User
                @else
                    Create User
                @endif
            </h2>
            <button wire:click="$set('showUsersTable', !$showUsersTable)" 
                    class="text-primary-600 hover:text-primary-800">
                {{ $showUsersTable ? 'Hide' : 'Show' }} Users List
            </button>
        </div>

        @if($showUserForm)
            <div class="grid grid-cols-2 gap-4">
                <input wire:model="user_name" class="border rounded p-2" placeholder="Name">
                <input wire:model="user_email" class="border rounded p-2" placeholder="Email">
                <input wire:model="user_password" type="password" class="border rounded p-2" placeholder="Password">

                <select wire:model="user_role" class="border rounded p-2">
                    <option value="">Select Role</option>
                    @foreach($this->roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>

                <div class="col-span-2 flex gap-2">
                    @if($editingUserId)
                        <button wire:click="updateUser"
                            class="bg-green-600 text-white px-4 py-2 rounded">
                            Update User
                        </button>
                        <button wire:click="cancelUserEdit"
                            class="bg-gray-500 text-white px-4 py-2 rounded">
                            Cancel
                        </button>
                    @else
                        <button wire:click="createUser"
                            class="bg-primary-600 text-white px-4 py-2 rounded">
                            Create User
                        </button>
                    @endif
                </div>
            </div>
        @else
            <button wire:click="showCreateUserForm"
                class="bg-primary-600 text-white px-4 py-2 rounded mb-4">
                + Create New User
            </button>
        @endif

        {{-- Non-Admin Users Table --}}
        @if($showUsersTable)
            <div class="mt-6">
                <h3 class="font-semibold mb-2">Non-Admin Users</h3>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-left">Name</th>
                            <th class="border p-2 text-left">Email</th>
                            <th class="border p-2 text-left">Role</th>
                            <th class="border p-2 text-left">Created At</th>
                            <th class="border p-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->nonAdminUsers as $user)
                            <tr>
                                <td class="border p-2">{{ $user->name }}</td>
                                <td class="border p-2">{{ $user->email }}</td>
                                <td class="border p-2">{{ $user->role ?? 'No Role' }}</td>
                                <td class="border p-2">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td class="border p-2">
                                    <button wire:click="editUser({{ $user->id }})" 
                                            class="text-blue-600 hover:text-blue-800 mr-2">
                                        Edit
                                    </button>
                                    <button wire:click="deleteUser({{ $user->id }})" 
                                            wire:confirm="Are you sure you want to delete this user?"
                                            class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border p-4 text-center text-gray-500">
                                    No non-admin users found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::card>

</x-filament::page>