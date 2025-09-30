<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $users = User::with(['roles', 'teams', 'profile'])->orderBy('name')->get();

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $user = User::create($this->extractUserAttributes($data));

        $this->syncRelations($user, $data);

        return response()->json($user->load(['roles', 'teams', 'profile']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user->load(['roles', 'teams', 'profile']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $this->validatedData($request, $user->id);

        $user->fill($this->extractUserAttributes($data));

        if (array_key_exists('password', $data)) {
            $user->password = $data['password'];
        }

        $user->save();

        $this->syncRelations($user, $data);

        return response()->json($user->load(['roles', 'teams', 'profile']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }

    /**
     * Validate incoming user payload.
     *
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?int $userId = null): array
    {
        return $request->validate([
            'name' => [$userId ? 'sometimes' : 'required', 'string', 'max:191'],
            'email' => [$userId ? 'sometimes' : 'required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$userId ? 'sometimes' : 'required', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'teams' => ['nullable', 'array'],
            'teams.*.id' => ['required', 'integer', Rule::exists('teams', 'id')],
            'teams.*.role' => ['nullable', 'string', 'max:191'],
            'profile' => ['nullable', 'array'],
            'profile.first_name' => ['nullable', 'string', 'max:191'],
            'profile.last_name' => ['nullable', 'string', 'max:191'],
            'profile.phone' => ['nullable', 'string', 'max:50'],
            'profile.meta' => ['nullable', 'array'],
        ]);
    }

    /**
     * Sync related models (roles, teams, profile).
     */
    protected function syncRelations(User $user, array $data): void
    {
        if (array_key_exists('roles', $data)) {
            $user->roles()->sync($data['roles'] ?? []);
        }

        if (array_key_exists('teams', $data)) {
            $pivotData = collect($data['teams'] ?? [])
                ->mapWithKeys(fn (array $team) => [$team['id'] => ['role' => $team['role'] ?? null]]);
            $user->teams()->sync($pivotData);
        }

        if (array_key_exists('profile', $data)) {
            if (! empty($data['profile'])) {
                $user->profile()->updateOrCreate([], $data['profile']);
            } else {
                $user->profile()->delete();
            }
        }
    }

    /**
     * Extract fillable user attributes from validated payload.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function extractUserAttributes(array $data): array
    {
        $attributes = [];

        foreach (['name', 'email', 'password'] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        return $attributes;
    }
}
