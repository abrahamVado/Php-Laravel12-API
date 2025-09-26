<?php

namespace App\Http\Controllers\Secure;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurePageController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        return $this->sectionResponse('dashboard', $request);
    }

    public function users(Request $request): JsonResponse
    {
        return $this->sectionResponse('users', $request);
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->sectionResponse('profile', $request);
    }

    public function logs(Request $request): JsonResponse
    {
        return $this->sectionResponse('logs', $request);
    }

    public function errors(Request $request): JsonResponse
    {
        return $this->sectionResponse('errors', $request);
    }

    private function sectionResponse(string $section, Request $request): JsonResponse
    {
        return (new UserResource($request->user()))
            ->additional([
                'meta' => [
                    'section' => $section,
                    'message' => 'Authenticated access granted.',
                ],
            ])
            ->response();
    }
}
