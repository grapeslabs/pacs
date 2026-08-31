<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\MoonShine\Resources\BaseModelResource;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DetailCardController extends Controller
{
    public function show(string $resource, int|string $id): View
    {
        $target = moonshine()->getResources()->findByUri($resource);

        if (! $target instanceof BaseModelResource || ! $target->usesDetailSidebar()) {
            throw new NotFoundHttpException();
        }

        if (! $target->can(\MoonShine\Laravel\Enums\Ability::VIEW_ANY)) {
            throw new NotFoundHttpException();
        }

        $item = $target->findItemForDetail($id);

        return view('components.detail.card', [
            'data' => $target->detailSidebarData($item),
        ]);
    }
}
