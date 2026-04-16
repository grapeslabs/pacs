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

    public function show(int $id): JsonResponse
    {
        $key = Key::with(['person'])->find($id);

        if (!$key) {
            return response()->json(['message' => 'Ключ не найден'], 404);
        }

        return response()->json($this->formatKey($key));
    }

    public function store(StoreKeyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $key = Key::create($data);

            return response()->json($this->formatKey($key->load(['person'])), 201);
        } catch (\Exception $e) {
            Log::error('Ошибка создания ключа', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        }
    }

    public function update(UpdateKeyRequest $request, int $id): JsonResponse
    {
        $key = Key::find($id);

        if (!$key) {
            return response()->json(['message' => 'Ключ не найден'], 404);
        }

        try {
            $data = $request->validated();

            $key->update($data);

            return response()->json($this->formatKey($key->fresh(['person'])));
        } catch (\Exception $e) {
            Log::error('Ошибка обновления ключа', [
                'key_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $key = Key::find($id);

        if (!$key) {
            return response()->json(['message' => 'Ключ не найден'], 404);
        }

        try {
            $key->delete();

            return response()->json(['message' => 'Ключ успешно удален']);
        } catch (\Exception $e) {
            Log::error('Ошибка удаления ключа', [
                'key_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        }
    }

    private function formatKey(Key $key): array
    {
        return $key->toArray();
    }
}
