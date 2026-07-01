<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->mapWithKeys(fn ($s) => [$s->key => $s->value]);

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required',
        ]);

        foreach ($request->settings as $key => $value) {
            Setting::set($key, $value);
        }

        $settings = Setting::all()->mapWithKeys(fn ($s) => [$s->key => $s->value]);

        return response()->json(['message' => 'Settings updated', 'data' => $settings]);
    }
}
