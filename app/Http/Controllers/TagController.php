<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::select('id', 'name')->get();

        return response()->json($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name'
        ]);

        $tag = Tag::create([
            'name' => $request->name,
            'short_name' => $request->name
        ]);

        return response()->json([
            'id' => $tag->id,
            'name' => $tag->name
        ]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        return response()->json([
            'id' => $tag->id,
            'success' => $tag->delete()
        ]);
    }
}
