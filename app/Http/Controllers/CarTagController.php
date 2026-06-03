<?php

namespace App\Http\Controllers;

use App\Models\CarTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarTagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(CarTag::select('id', 'name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:car_tags,name',
        ]);

        $tag = CarTag::create([
            'name'       => $request->name,
            'short_name' => $request->name,
        ]);

        return response()->json(['id' => $tag->id, 'name' => $tag->name]);
    }

    public function destroy(CarTag $carTag): JsonResponse
    {
        return response()->json(['id' => $carTag->id, 'success' => $carTag->delete()]);
    }
}
