<?php

namespace App\MoonShine\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\Support\Enums\ToastType;

trait HasUndoNotification
{
    public function notifyDeletedWithUndo(mixed $item): void
    {
        if (! $item instanceof Model) {
            return;
        }

        $undoUrl = $this->getAsyncMethodUrl('restoreWithUndo', params: ['resourceItem' => $item->getKey()]);

        session()->flash('moonshine_custom_toast', [
            'message' => 'Запись успешно удалена',
            'undoUrl' => $undoUrl
        ]);
    }

    public function restoreWithUndo(Request $request): MoonShineJsonResponse
    {
        $id = $request->input('resourceItem');

        if ($id) {
            $item = $this->getModel()->withTrashed()->find($id);

            if ($item) {
                $item->restore();
            }
        }

        return MoonShineJsonResponse::make()
            ->toast('Запись успешно восстановлена', ToastType::SUCCESS)
            ->events([
                AlpineJs::event(JsEvent::TABLE_UPDATED, $this->getListComponentName())
            ]);
    }
}
