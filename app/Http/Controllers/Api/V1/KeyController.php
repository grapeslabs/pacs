<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreKeyRequest;
use App\Http\Requests\Api\V1\UpdateKeyRequest;
use App\Models\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Key::with(['person']);

        if ($request->filled('key')) {
            $query->where('key', 'like', '%' . $request->input('key') . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('person_id')) {
            $query->where('person_id', $request->input('person_id'));
        }

        $total = $query->count();

        $limit = (int) $request->input('limit', 15);
        $offset = (int) $request->input('offset', 0);

        $keys = $query->offset($offset)->limit($limit)->get();

        $data = $keys->map(fn($key) => $this->formatKey($key))->toArray();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    public function show(Key $keyItem): JsonResponse
    {
        return response()->json($this->formatKey($keyItem->load('person')));
    }

    public function store(StoreKeyRequest $request): JsonResponse
    {
        $key = Key::create($request->validated());
        return response()->json($this->formatKey($key->load(['person'])), 201);
    }

    public function update(UpdateKeyRequest $request, Key $keyItem): JsonResponse
    {
        $keyItem->update($request->validated());
        return response()->json($this->formatKey($keyItem->load(['person'])));
    }

    public function destroy(Key $key): JsonResponse
    {
        $key->delete();
        return response()->json(['message' => 'Ключ успешно удален']);
    }

    private function formatKey(Key $key): array
    {
        return $key->toArray();
    }
}
