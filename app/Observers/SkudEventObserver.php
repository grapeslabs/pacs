<?php

namespace App\Observers;

use GrapesLabs\PinvideoSkud\Models\SkudEvent;
use App\Models\SkudEventCarPlate;
use App\Models\SkudEventPerson;

class SkudEventObserver
{
    /**
     * Handle the SkudEvent "created" event.
     */
    public function created(SkudEvent $event): void
    {
        $this->syncCarPlate($event);
        $this->syncEventPerson($event);
    }

    /**
     * Handle the SkudEvent "updated" event.
     */
    public function updated(SkudEvent $event): void
    {
        $this->syncCarPlate($event);
        $this->syncEventPerson($event);
    }

    /**
     * Handle the SkudEvent "deleted" event.
     */
    public function deleted(SkudEvent $event): void
    {
        SkudEventCarPlate::where('event_id', $event->id)->delete();
        SkudEventPerson::where('event_id', $event->id)->delete();
    }

    /**
     * Handle the SkudEvent "restored" event.
     */
    public function restored(SkudEvent $event): void
    {
        $this->syncCarPlate($event);
        $this->syncEventPerson($event);
    }

    /**
     * Handle the SkudEvent "force deleted" event.
     */
    public function forceDeleted(SkudEvent $event): void
    {
        SkudEventCarPlate::where('event_id', $event->id)->delete();
        SkudEventPerson::where('event_id', $event->id)->delete();
    }

    /**
     * Синхронизировать номер автомобиля с отдельной таблицей
     */
    private function syncCarPlate(SkudEvent $event): void
    {
        $eventData = json_decode($event->event ?? '{}', true);
        $carPlate = $eventData['car_plate'] ?? null;

        if ($carPlate) {
            SkudEventCarPlate::updateOrCreate(
                ['event_id' => $event->id],
                ['car_plate' => $carPlate]
            );
        } else {
            SkudEventCarPlate::where('event_id', $event->id)->delete();
        }
    }

    /**
     * Синхронизировать номер карты/персоны с отдельной таблицей
     */
    private function syncEventPerson(SkudEvent $event): void
    {
        $eventData = json_decode($event->event ?? '{}', true);
        $cardNumber = $eventData['card_number'] ?? null;

        if ($cardNumber) {
            SkudEventPerson::updateOrCreate(
                ['event_id' => $event->id],
                ['card_number' => $cardNumber]
            );
        } else {
            SkudEventPerson::where('event_id', $event->id)->delete();
        }
    }
}
