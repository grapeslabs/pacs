<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use MoonShine\ImportExport\ImportHandler;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeMoonShineAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if ($routeName === 'moonshine.method') {
            $this->authorizeMethod($request);
        }

        if ($routeName === 'moonshine.handler') {
            $this->authorizeHandler($request);
        }

        return $next($request);
    }

    protected function authorizeMethod(Request $request): void
    {
        $resource = $this->resource($request);
        $method = (string) $request->input('method');

        $map = method_exists($resource, 'asyncMethodAbilities')
            ? $resource->asyncMethodAbilities()
            : [];

        abort_unless(isset($map[$method]), 403);
        abort_unless($map[$method] instanceof Ability, 403);
        abort_unless($resource->can($map[$method]), 403);
    }

    protected function authorizeHandler(Request $request): void
    {
        $resource = $this->resource($request);
        $handler = method_exists($resource, 'getHandlers')
            ? $resource->getHandlers()->findByUri((string) $request->route('handlerUri'))
            : null;

        abort_if($handler === null, 403);

        abort_unless(
            $resource->can($handler instanceof ImportHandler ? Ability::CREATE : Ability::VIEW_ANY),
            403
        );
    }

    protected function resource(Request $request): object
    {
        abort_unless(MoonShineAuth::getGuard()->user() instanceof User, 403);

        $resourceUri = $request->route('resourceUri');

        abort_if($resourceUri === null, 403);

        $resource = moonshine()->getResources()->findByUri((string) $resourceUri);

        abort_if($resource === null, 403);

        return $resource;
    }
}
