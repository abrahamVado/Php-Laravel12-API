<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $teams = Team::with('users')->orderBy('name')->get();

        return response()->json($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $team = Team::create(Arr::only($data, ['name', 'description']));

        $this->syncMembers($team, $data);

        return response()->json($team->load('users'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team): JsonResponse
    {
        return response()->json($team->load('users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team): JsonResponse
    {
        $data = $this->validatedData($request, $team->id);

        $team->fill(Arr::only($data, ['name', 'description']));
        $team->save();

        $this->syncMembers($team, $data);

        return response()->json($team->load('users'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team): Response
    {
        $team->delete();

        return response()->noContent();
    }

    /**
     * Validate request payload.
     *
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?int $teamId = null): array
    {
        return $request->validate([
            'name' => [$teamId ? 'sometimes' : 'required', 'string', 'max:191', Rule::unique('teams', 'name')->ignore($teamId)],
            'description' => ['nullable', 'string', 'max:500'],
            'members' => ['nullable', 'array'],
            'members.*.id' => ['required', 'integer', Rule::exists('users', 'id')],
            'members.*.role' => ['nullable', 'string', 'max:191'],
        ]);
    }

    /**
     * Sync members if provided.
     */
    protected function syncMembers(Team $team, array $data): void
    {
        if (array_key_exists('members', $data)) {
            $payload = collect($data['members'] ?? [])
                ->mapWithKeys(fn (array $member) => [$member['id'] => ['role' => $member['role'] ?? null]]);
            $team->users()->sync($payload);
        }
    }
}
