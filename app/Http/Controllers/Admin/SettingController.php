<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(Setting::orderBy('key')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $setting = Setting::create($data);

        return response()->json($setting, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting): JsonResponse
    {
        return response()->json($setting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setting $setting): JsonResponse
    {
        $data = $this->validatedData($request, $setting->id);

        $setting->fill($data);
        $setting->save();

        return response()->json($setting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting): Response
    {
        $setting->delete();

        return response()->noContent();
    }

    /**
     * Validate setting payload.
     *
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?int $settingId = null): array
    {
        return $request->validate([
            'key' => [$settingId ? 'sometimes' : 'required', 'string', 'max:191', Rule::unique('settings', 'key')->ignore($settingId)],
            'value' => ['nullable'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
