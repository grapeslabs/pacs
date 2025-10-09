<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadStatusRequest;
use App\Http\Requests\DownloadArchiveRequest;
use App\Models\Stream;
use App\Services\MediaServerService;
use App\Services\VideoAnalyticService;
use Carbon\Carbon;

class StreamController extends Controller
{
    public function __construct(
        protected MediaServerService $mss,
        protected VideoAnalyticService $vas
    ) {}

    public function downloadArchive(DownloadArchiveRequest $request, Stream $stream) {
        $startTime = Carbon::createFromTimestamp($request->start_time)->toIso8601String();
        $endTime = Carbon::createFromTimestamp($request->end_time)->toIso8601String();
        return $this->mss->downloadArchive($stream->uid, $startTime, $endTime);
    }
    public function downloadStatus(DownloadStatusRequest $request, Stream $stream) {
        return $this->mss->downloadStatus($stream->uid, $request->requestId);
    }

    public function downloadArchiveFile(DownloadStatusRequest $request, Stream $stream) {
        return $this->mss->downloadArchiveFile($stream->uid, $request->requestId);
    }
}
