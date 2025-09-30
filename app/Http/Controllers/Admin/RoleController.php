<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return response()->json($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:roles,name'],
            'display_name' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $role = Role::create(Arr::except($data, ['permissions']));

        if (! empty($data['permissions'] ?? [])) {
            $role->permissions()->sync($data['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', Rule::unique('roles', 'name')->ignore($role->id)],
            'display_name' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $role->fill(Arr::except($data, ['permissions']));
        $role->save();

        if (array_key_exists('permissions', $data)) {
            $role->permissions()->sync($data['permissions'] ?? []);
        }

        return response()->json($role->load('permissions'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): Response
    {
        $role->delete();

        return response()->noContent();
    }
}
