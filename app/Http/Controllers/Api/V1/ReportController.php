<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Analytics\Event;
use App\Models\VideoAnalyticReport;
use App\Models\Person;
use App\Http\Requests\Api\V1\IdentifyPersonRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReportController extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $query = VideoAnalyticReport::with(['stream', 'person']);

        if ($request->filled('datetime_from')) {
            $query->where('datetime', '>=', Carbon::parse($request->input('datetime_from')));
        }
        if ($request->filled('datetime_to')) {
            $query->where('datetime', '<=', Carbon::parse($request->input('datetime_to')));
        }
        if ($request->filled('camera_id')) {
            $query->whereHas('stream', fn($q) => $q->where('uid', $request->input('camera_id')));
        }
        if ($request->filled('is_unknown')) {
            $query->where('is_unknown', filter_var($request->input('is_unknown'), FILTER_VALIDATE_BOOLEAN));
        }

        $reports = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($this->formatReports($reports));
    }

    public function people(Request $request): JsonResponse
    {
        $query = VideoAnalyticReport::with(['stream', 'person'])->where('is_unknown', false);

        if ($request->filled('datetime_from')) {
            $query->where('datetime', '>=', Carbon::parse($request->input('datetime_from')));
        }
        if ($request->filled('datetime_to')) {
            $query->where('datetime', '<=', Carbon::parse($request->input('datetime_to')));
        }
        if ($request->filled('camera_id')) {
            $query->whereHas('stream', fn($q) => $q->where('uid', $request->input('camera_id')));
        }
        if ($request->filled('person_photobank_id')) {
            $query->where('person_photobank_id', $request->input('person_photobank_id'));
        }

        $reports = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($this->formatReports($reports));
    }

    public function unknown(Request $request): JsonResponse
    {
        $query = VideoAnalyticReport::with(['stream'])->where('is_unknown', true);

        if ($request->filled('datetime_from')) {
            $query->where('datetime', '>=', Carbon::parse($request->input('datetime_from')));
        }
        if ($request->filled('datetime_to')) {
            $query->where('datetime', '<=', Carbon::parse($request->input('datetime_to')));
        }
        if ($request->filled('camera_id')) {
            $query->whereHas('stream', fn($q) => $q->where('uid', $request->input('camera_id')));
        }

        $reports = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($this->formatReports($reports));
    }

    public function identify(IdentifyPersonRequest $request, int $reportId): JsonResponse
    {
        $report = VideoAnalyticReport::where('is_unknown', true)->findOrFail($reportId);
        $data = $request->validated();

        $person = Person::create([
            'grapesva_uuid' => $data['grapesva_uuid'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'certificate_number' => $data['certificate_number'] ?? null,
            'organization_id' => $data['organization_id'] ?? null,
            'comment' => $data['comment'] ?? null,
        ]);

        if (!empty($data['tags'])) {
            $person->tags()->sync($data['tags']);
        }

        $finalPhotos = [];

        if ($request->hasFile('photo')) {
            $files = $request->file('photo');
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                $finalPhotos[] = $file->store('person/photos', 'public');
            }
        } elseif (!empty($report->data['snapshot_path'])) {
            $filename = basename($report->data['snapshot_path']);
            $sourcePath = 'thumbnails/' . $filename;
            $newPath = 'person/photos/' . $filename;

            if (Storage::disk('analytic')->exists($sourcePath)) {
                $content = Storage::disk('analytic')->get($sourcePath);
                Storage::disk('public')->put($newPath, $content);
                $finalPhotos[] = $newPath;
            }
        }

        $person->photo = $finalPhotos;
        $person->saveQuietly();

        VideoAnalyticReport::query()->toBase()
            ->where('person_photobank_id', $person->grapesva_uuid)
            ->update(['is_unknown' => false]);

        return response()->json([
            'message' => 'Персона успешно идентифицирована',
            'person' => $person
        ]);
    }

    private function formatReports(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(function ($item) {
                $snapshotUrl = null;
                if (!empty($item->data['snapshot_path'])) {
                    try {
                        $snapshotUrl = Storage::disk('analytic')->url('thumbnails/' . basename($item->data['snapshot_path']));
                    } catch (Throwable $e) {
                        Log::error('Ошибка генерации URL для фото', [
                            'report_id' => $item->id,
                            'error' => $e->getMessage(),
                        ]);
                        $snapshotUrl = null;
                    }
                }

                return [
                    'id' => $item->id,
                    'datetime' => $item->datetime,
                    'is_unknown' => $item->is_unknown,
                    'person_photobank_id' => $item->person_photobank_id,
                    'snapshot_url' => $snapshotUrl,
                    'stream' => $item->stream ? [
                        'id' => $item->stream->id,
                        'uid' => $item->stream->uid,
                        'name' => $item->stream->name,
                    ] : null,
                    'person' => $item->person ? [
                        'id' => $item->person->id,
                        'last_name' => $item->person->last_name,
                        'first_name' => $item->person->first_name,
                        'middle_name' => $item->person->middle_name,
                        'birth_date' => $item->person->birth_date,
                        'tags' => $item->person->tags_list ?? [],
                    ] : null,
                ];
            })->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ];
    }
}
