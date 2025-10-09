<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Services\Otp\ClientId;
use App\Services\Otp\Password;
use App\Services\Otp\OtpManager;

class GuestApiController extends Controller
{
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }

        return $digits;
    }

    public function auth(Request $request, OtpManager $otpManager): JsonResponse|Response
    {
        try {
            $request->validate([
                'phone' => 'required|string|max:20',
                'organization_id' => 'required|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $normalizedPhone = $this->normalizePhone($request->phone);

        $guest = Guest::all()->first(function ($guest) use ($normalizedPhone) {
            return $this->normalizePhone($guest->phone) === $normalizedPhone;
        });

        if (!$guest) {
            return response()->json([
                'message' => 'Guest not found.',
                'errors' => [
                    'phone' => ['Guest with this phone number not found.']
                ]
            ], 404);
        }
        $now = Carbon::now();
        if ($guest['entry_start'] !== null && $now->lt($guest['entry_start'])) {
            return response()->json([
                'message' => 'Guest invite is not yet active.',
                'errors' => ['phone' => ['Invite for this phone number is not yet valid.']]
            ], 403);
        }

        if ($guest['entry_end'] !== null && $now->gt($guest['entry_end'])) {
            return response()->json([
                'message' => 'Guest invite has expired.',
                'errors' => ['phone' => ['Invite for this phone number has expired.']]
            ], 410);
        }

        $otp = $otpManager->driver();
        $otp->send(new ClientId($normalizedPhone));

        return response()->json(null, 204);
    }

    public function confirm(Request $request, OtpManager $otpManager): JsonResponse
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'code' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        }

        $normalizedPhone = $this->normalizePhone($request->phone);

        $guest = Guest::all()->first(function ($guest) use ($normalizedPhone) {
            return $this->normalizePhone($guest->phone) === $normalizedPhone;
        });

        if (!$guest) {
            return response()->json([
                'message' => 'Guest access not requested for this phone.'
            ], 404);
        }

        $otp = $otpManager->driver();
        $check = $otp->check(new ClientId($normalizedPhone), new Password($request->code));
        if ($check) {
            return response()->json([
                'status' => 'accepted',
                'guest_id' => $guest->id,
                'guest_data' => [
                    'id' => $guest->id,
                    'external_id' => $guest->external_id,
                    'full_name' => $guest->full_name,
                    'phone' => $guest->phone,
                    'entry_start' => $guest->entry_start,
                    'entry_end' => $guest->entry_end,
                ]
            ]);
        }

        return response()->json([
            'status' => 'rejected'
        ]);
    }

    public function storePhotos(Request $request, $id): JsonResponse|Response
    {
        $guest = Guest::find($id);
        if (!$guest) {
            return response()->json(['message' => 'Guest not found.'], 404);
        }

        try {
            $request->validate([
                'photos' => 'required|array',
                'photos.*.photo' => 'required_without_all:photos.*.avatar,photos.*.vector',
                'photos.*.avatar' => 'required_without_all:photos.*.photo,photos.*.vector',
                'photos.*.vector' => 'required_without_all:photos.*.photo,photos.*.avatar',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        }

        $savedPaths = [];

        foreach ($request->photos as $index => $photoData) {
            if (isset($photoData['photo'])) {
                $base64Data = $photoData['photo'];
                $imageData = base64_decode($base64Data);
                $filename = "guest_{$guest->id}_photo_{$index}_" . time() . ".jpg";
                $path = "guests/photos/{$filename}";

                Storage::disk('public')->put($path, $imageData);
                $savedPaths[] = $path;
            }
        }

        $guest->update([
            'photo' => $savedPaths
        ]);

        $this->addGuestToSkud($guest);

        return response()->noContent();
    }

    protected function addGuestToSkud(Guest $guest): void
    {
        try {
            if (!class_exists(\GrapesLabs\PinvideoSkud\Models\SkudController::class)) {
                return;
            }

            $controllers = \GrapesLabs\PinvideoSkud\Models\SkudController::all();
            $images = [];

            if (!empty($guest->photo)) {
                foreach ($guest->photo as $photoPath) {
                    $fullPath = Storage::disk('public')->path($photoPath);
                    if (file_exists($fullPath)) {
                        $binaryContent = file_get_contents($fullPath);

                        if ($this->isBase64Encoded($binaryContent)) {
                            $binaryContent = base64_decode($binaryContent);
                        }

                        $images[] = [
                            'id' => pathinfo($photoPath, PATHINFO_FILENAME),
                            'content' => $binaryContent
                        ];
                    }
                }
            }

            foreach ($controllers as $controller) {
                try {
                    $skudUid = 'guest_' . $guest->id;

                    $key = new \GrapesLabs\PinvideoSkud\Keys\FaceIdKey(
                        uid: $skudUid,
                        images: $images,
                        name: $guest->full_name ?: 'Guest ' . $guest->phone
                    );

                    $skudController = \GrapesLabs\PinvideoSkud\ControllerFactory::create($controller);
                    $skudController->writeKeys([$key]);

                } catch (\Exception $e) {
                    Log::error('SKUD controller error for guest ' . $guest->id . ': ' . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error('SKUD integration failed for guest ' . $guest->id);
        }
    }

    protected function isBase64Encoded(string $data): bool
    {
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $data;
    }

    protected function removeGuestFromSkud(Guest $guest): void
    {
        try {
            if (!class_exists(\GrapesLabs\PinvideoSkud\Models\SkudController::class)) {
                return;
            }

            $controllers = \GrapesLabs\PinvideoSkud\Models\SkudController::all();
            $skudUid = 'guest_' . $guest->id;

            foreach ($controllers as $controller) {
                try {
                    $key = new \GrapesLabs\PinvideoSkud\Keys\FaceIdKey(
                        uid: $skudUid,
                        images: [],
                        name: $guest->full_name ?: 'Guest ' . $guest->phone
                    );

                    $skudController = \GrapesLabs\PinvideoSkud\ControllerFactory::create($controller);
                    $skudController->clearKeys([$key]);

                } catch (\Exception $e) {
                    Log::error('SKUD remove error for guest ' . $guest->id . ': ' . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error('SKUD removal failed for guest ' . $guest->id);
        }
    }
}
