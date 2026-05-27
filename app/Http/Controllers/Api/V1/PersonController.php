<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePersonRequest;
use App\Http\Requests\Api\V1\UpdatePersonRequest;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PersonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Person::with(['tags', 'organization']);

        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%' . $request->input('last_name') . '%');
        }

        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%' . $request->input('first_name') . '%');
        }

        if ($request->filled('middle_name')) {
            $query->where('middle_name', 'like', '%' . $request->input('middle_name') . '%');
        }

        if ($request->filled('certificate_number')) {
            $query->where('certificate_number', 'like', '%' . $request->input('certificate_number') . '%');
        }

        if ($request->filled('birth_date')) {
            $query->whereDate('birth_date', $request->input('birth_date'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('tags') && is_array($request->input('tags'))) {
            $tags = $request->input('tags');
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('tags.id', $tags);
            });
        }

        $total = $query->count();

        $limit = (int) $request->input('limit', 15);
        $offset = (int) $request->input('offset', 0);

        $persons = $query->offset($offset)->limit($limit)->get();

        $data = $persons->map(fn($person) => $this->formatPerson($person))->toArray();

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
        $person = Person::with(['tags', 'organization', 'keys'])->find($id);

        if (!$person) {
            return response()->json(['message' => 'Персона не найдена'], 404);
        }

        return response()->json($this->formatPerson($person));
    }

    public function store(StorePersonRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if (isset($data['photo'])) {
                $data['photo'] = $this->processPhotos($data['photo']);
            }

            $person = Person::create($data);

            if ($request->has('tags')) {
                $person->tags()->sync($request->input('tags'));
            }

            DB::commit();

            return response()->json($this->formatPerson($person->load(['tags', 'organization'])), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка создания персоны', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        }
    }

    public function update(UpdatePersonRequest $request, int $id): JsonResponse
    {
        $person = Person::find($id);

        if (!$person) {
            return response()->json(['message' => 'Персона не найдена'], 404);
        }

        try {
            DB::beginTransaction();

            $data = $request->validated();

            if ($request->has('photo')) {
                $data['photo'] = $this->processPhotos($data['photo']);
            }

            $person->update($data);

            if ($request->has('tags')) {
                $person->tags()->sync($request->input('tags'));
            }

            DB::commit();

            return response()->json($this->formatPerson($person->fresh(['tags', 'organization'])));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка обновления персоны', [
                'person_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $person = Person::find($id);

        if (!$person) {
            return response()->json(['message' => 'Персона не найдена'], 404);
        }

        try {
            DB::beginTransaction();
            $person->delete();
            DB::commit();

            return response()->json(['message' => 'Персона успешно удалена']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка удаления персоны', [
                'person_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        }
    }

    private function processPhotos(mixed $photos): ?array
    {
        if ($photos === null) {
            return null;
        }

        if (!is_array($photos)) {
            $photos = [$photos];
        }

        $paths = [];

        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $paths[] = $photo->store('person/photos', 'public');
            } elseif (is_string($photo)) {
                $paths[] = $photo;
            }
        }

        return array_values($paths);
    }

    private function formatPerson(Person $person): array
    {
        $data = $person->toArray();

        if (!empty($data['photo']) && is_array($data['photo'])) {
            $data['photo'] = array_map(function ($path) {
                return Storage::disk('public')->url($path);
            }, $data['photo']);
        }

        return $data;
    }
}
