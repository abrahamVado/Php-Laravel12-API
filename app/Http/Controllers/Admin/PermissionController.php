<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get();

        return response()->json($permissions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:permissions,name'],
            'display_name' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $permission = Permission::create($data);

        return response()->json($permission, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): JsonResponse
    {
        return response()->json($permission);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'display_name' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $permission->fill($data);
        $permission->save();

        return response()->json($permission);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission): Response
    {
        $permission->delete();

        return response()->noContent();
    }
}
