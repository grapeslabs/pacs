<?php

namespace App\Observers;

use App\Models\Car;
use App\Models\CarPassageEvent;
use App\Services\VideoAnalyticLprService;
use Illuminate\Support\Facades\Log;

class CarObserver
{
    public function __construct(
        protected VideoAnalyticLprService $lpr,
    ) {}

    public function created(Car $car): void
    {
        $this->linkPassageEvents($car);

        if (!config('services.lpr.enabled')) {
            return;
        }

        $result = $this->lpr->carCreate($car->license_plate, $car->comment ?? '');
        if (empty($result['ok'])) {
            Log::warning('CarObserver: LPR car create failed', ['plate' => $car->license_plate]);
        }
    }

    public function updated(Car $car): void
    {
        if ($car->wasChanged('license_plate')) {
            CarPassageEvent::query()
                ->where('car_id', $car->id)
                ->update(['car_id' => null]);

            $this->linkPassageEvents($car);
        }

        if (!config('services.lpr.enabled')) {
            return;
        }

        if (!$car->wasChanged(['license_plate', 'comment'])) {
            return;
        }

        $oldPlate = $car->getOriginal('license_plate');

        $result = $this->lpr->carDelete($oldPlate);
        if (empty($result['ok'])) {
            Log::warning('CarObserver: LPR car delete failed on update', ['plate' => $oldPlate]);
        }

        $result = $this->lpr->carCreate($car->license_plate, $car->comment ?? '');
        if (empty($result['ok'])) {
            Log::warning('CarObserver: LPR car create failed on update', ['plate' => $car->license_plate]);
        }
    }

    public function deleted(Car $car): void
    {
        if (!config('services.lpr.enabled')) {
            return;
        }

        $result = $this->lpr->carDelete($car->license_plate);
        if (empty($result['ok'])) {
            Log::warning('CarObserver: LPR car delete failed', ['plate' => $car->license_plate]);
        }
    }

    public function restored(Car $car): void
    {
        if (!config('services.lpr.enabled')) {
            return;
        }

        $result = $this->lpr->carCreate($car->license_plate, $car->comment ?? '');
        if (empty($result['ok'])) {
            Log::warning('CarObserver: LPR car create failed on restore', ['plate' => $car->license_plate]);
        }
    }

    protected function linkPassageEvents(Car $car): void
    {
        if (empty($car->license_plate)) {
            return;
        }

        $normalizedPlate = mb_strtoupper(str_replace(' ', '', $car->license_plate));

        CarPassageEvent::query()
            ->whereNull('car_id')
            ->whereRaw("upper(replace(plate_text, ' ', '')) = ?", [$normalizedPlate])
            ->update(['car_id' => $car->id]);
    }
}
