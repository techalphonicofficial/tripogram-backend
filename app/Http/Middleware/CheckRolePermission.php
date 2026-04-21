<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Illuminate\Support\Facades\DB;


class CheckRolePermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
// dd($user);
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Admin & Manager bypass
        if (in_array($user->role, ['admin'])) {
         
           return $next($request);
        }

// dd('sd');
        $role = Role::where('name', $user->role)->first();
          $role_id = $role->id;
        if (!$role_id) {
            return false;
        }
        if (!$role) {
            abort(403, 'Role not found');
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




        // Get route name (ex: users.index)
        $routeName = $request->route()->getName();

        // Extract resource (users, posts, etc.)
        $resource = explode('.', $routeName)[3];
// dd($resource);   
    if($resource == 'dashboard'){
         return $next($request);
    }
    else if($resource == 'logout'){
        return $next($request);
    }
      if (!in_array($resource, $permissionResources)) {
    abort(403, 'You do not have permission to access this resource');
}

        // dd($resource);
        // Check permission


        // $hasPermission = $role->permissions()
        //     ->where('name', $resource)
        //     ->exists();

        // if (!$hasPermission) {
        //     abort(403, 'You do not have permission to access this resource');
        // }

        return $next($request);
    }


    
}













