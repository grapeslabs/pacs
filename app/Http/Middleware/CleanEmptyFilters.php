<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CleanEmptyFilters
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['query', 'request'] as $bag) {
            $params = $request->{$bag}->all();

            if (! isset($params['filter']) || ! \is_array($params['filter'])) {
                continue;
            }

            $filter = $this->clean($params['filter']);

            if ($filter === []) {
                $request->{$bag}->remove('filter');

                continue;
            }

            $request->{$bag}->set('filter', $filter);
        }

        return $next($request);
    }

    private function clean(array $values): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                $value = $this->clean($value);
            }

            if (blank($value)) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
