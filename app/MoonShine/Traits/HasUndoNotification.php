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
    protected array $massDeletedIds = [];
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

    protected function afterMassDeleted(array $ids): void
    {
        if ($this->softDelete) {
            $this->massDeletedIds = array_values($ids);
        }
    }

    public function modifyMassDeleteResponse(MoonShineJsonResponse $response): MoonShineJsonResponse
    {
        if ($this->softDelete && ! empty($this->massDeletedIds)) {
            $data = $response->getData(true);
            $data['message'] = 'Записи успешно удалены';
            $data['undoUrl'] = $this->getAsyncMethodUrl(
                'restoreMassWithUndo',
                params: ['ids' => $this->massDeletedIds],
            );
            $response->setData($data);
        }

        return $response;
    }

    public function restoreMassWithUndo(Request $request): MoonShineJsonResponse
    {
        $ids = (array) $request->input('ids', []);

        if (! empty($ids)) {
            $this->getModel()
                ->withTrashed()
                ->whereIn($this->getModel()->getKeyName(), $ids)
                ->restore();
        }

        return MoonShineJsonResponse::make()
            ->toast('Записи успешно восстановлены', ToastType::SUCCESS)
            ->events([
                AlpineJs::event(JsEvent::TABLE_UPDATED, $this->getListComponentName())
            ]);
    }
}
