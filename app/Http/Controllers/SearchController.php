<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Stream;
use App\Models\User;
use App\MoonShine\Resources\CarResource;
use App\MoonShine\Resources\GuestResource;
use App\MoonShine\Resources\MoonShineUserResource;
use App\MoonShine\Resources\OrganizationResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\VideoStreamResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\MoonShineAuth;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! MoonShineAuth::getGuard()->check()) {
            abort(403);
        }

        $q = trim((string) $request->get('q', ''));

        $empty = ['persons' => [], 'organizations' => [], 'cars' => [], 'guests' => [], 'users' => [], 'cameras' => []];

        if (mb_strlen($q) < 1) {
            return response()->json($empty);
        }

        return response()->json([
            'persons' => $this->persons($q),
            'organizations' => $this->organizations($q),
            'cars' => $this->cars($q),
            'guests' => $this->guests($q),
            'users' => $this->users($q),
            'cameras' => $this->cameras($q),
        ]);
    }

    private function cars(string $q): array
    {
        try {
            return Car::query()
                ->where('license_plate', 'ilike', "%{$q}%")
                ->with('brand')
                ->limit(6)
                ->get()
                ->map(fn (Car $c) => [
                    'title' => $c->license_plate,
                    'subtitle' => 'Автомобиль · ' . ($c->brand?->name ?: '—'),
                    'url' => $this->resourceUrl(CarResource::class, $c->id),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function guests(string $q): array
    {
        try {
            return Guest::query()
                ->where(function ($w) use ($q) {
                    $w->where('full_name', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%");
                })
                ->limit(6)
                ->get()
                ->map(fn (Guest $g) => [
                    'title' => $g->full_name,
                    'subtitle' => 'Гость · ' . ($g->phone ?: '—'),
                    'url' => $this->resourceUrl(GuestResource::class, $g->id),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function users(string $q): array
    {
        try {
            return User::query()
                ->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%");
                })
                ->limit(6)
                ->get()
                ->map(fn (User $u) => [
                    'title' => $u->name ?: $u->email,
                    'subtitle' => 'Пользователь · ' . ($u->email ?: '—'),
                    'url' => $this->resourceUrl(MoonShineUserResource::class, $u->id),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function organizations(string $q): array
    {
        try {
            return Organization::query()
                ->where(function ($w) use ($q) {
                    $w->where('full_name', 'ilike', "%{$q}%")
                        ->orWhere('short_name', 'ilike', "%{$q}%");
                })
                ->limit(6)
                ->get()
                ->map(fn (Organization $o) => [
                    'title' => $o->short_name ?: $o->full_name,
                    'subtitle' => 'Организация · ' . ($o->full_name ?: '—'),
                    'url' => $this->resourceUrl(OrganizationResource::class, $o->id),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function persons(string $q): array
    {
        try {
            return Person::query()
                ->where(function ($w) use ($q) {
                    $w->where('last_name', 'ilike', "%{$q}%")
                        ->orWhere('first_name', 'ilike', "%{$q}%")
                        ->orWhere('middle_name', 'ilike', "%{$q}%");
                })
                ->with('organization')
                ->limit(6)
                ->get()
                ->map(fn (Person $p) => [
                    'title' => $p->getFullName(),
                    'subtitle' => 'Персона · ' . ($p->organization?->short_name ?: $p->organization?->full_name ?: '—'),
                    'avatar' => $this->personAvatar($p),
                    'url' => $this->resourceUrl(PersonResource::class, $p->id),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function cameras(string $q): array
    {
        if (! config('services.ms.enabled')) {
            return [];
        }

        try {
            return Stream::query()
                ->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('location', 'ilike', "%{$q}%");
                })
                ->limit(6)
                ->get()
                ->map(fn (Stream $s) => [
                    'title' => $s->name,
                    'subtitle' => 'Камера · ' . ($s->location ?: '—'),
                    'url' => $this->resourceUrl(VideoStreamResource::class, $s->id),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function personAvatar(Person $p): ?string
    {
        $photo = $p->first_photo;

        if (! $photo) {
            return null;
        }

        $path = str_contains((string) $photo, '/') ? $photo : 'person/photos/' . $photo;

        return rescue(fn (): string => Storage::disk('public')->url($path), null, report: false);
    }

    private function resourceUrl(string $resource, int|string $id): string
    {
        return rescue(
            fn (): string => app($resource)->getDetailPageUrl($id),
            rescue(fn (): string => app($resource)->getFormPageUrl($id), '#', report: false),
            report: false,
        );
    }
}
