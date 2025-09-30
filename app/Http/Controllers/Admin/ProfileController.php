<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $profiles = Profile::with('user')->orderBy('id')->get();

        return response()->json($profiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $profile = Profile::create($data);

        return response()->json($profile->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Profile $profile): JsonResponse
    {
        return response()->json($profile->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Profile $profile): JsonResponse
    {
        $data = $this->validatedData($request, $profile->id, $profile->user_id);

        $profile->fill($data);
        $profile->save();

        return response()->json($profile->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Profile $profile): Response
    {
        $profile->delete();

        return response()->noContent();
    }

    /**
     * Validate profile payload.
     *
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?int $profileId = null, ?int $userId = null): array
    {
        return $request->validate([
            'user_id' => [$profileId ? 'sometimes' : 'required', 'integer', Rule::exists('users', 'id'), Rule::unique('profiles', 'user_id')->ignore($userId, 'user_id')],
            'first_name' => ['nullable', 'string', 'max:191'],
            'last_name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'meta' => ['nullable', 'array'],
        ]);
    }
}
