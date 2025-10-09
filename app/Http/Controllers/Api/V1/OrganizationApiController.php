<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:1000',
        ]);

        $perPage = $request->get('per_page', 100);
        $organizations = Organization::select(['id', 'full_name', 'short_name', 'address'])
            ->paginate($perPage);

        $formattedData = [];
        foreach ($organizations->items() as $org) {
            $formattedData[] = [
                'id' => $org->id,
                'full_name' => $org->full_name,
                'short_name' => $org->short_name,
                'address' => $org->address
            ];
        }

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1/organizations';
        
        return response()->json([
            'data' => $formattedData,
            'links' => [
                'first' => $this->buildUrl($baseUrl, 1, $perPage),
                'last' => $this->buildUrl($baseUrl, $organizations->lastPage(), $perPage),
                'prev' => $organizations->currentPage() > 1 ? 
                    $this->buildUrl($baseUrl, $organizations->currentPage() - 1, $perPage) : null,
                'next' => $organizations->currentPage() < $organizations->lastPage() ? 
                    $this->buildUrl($baseUrl, $organizations->currentPage() + 1, $perPage) : null,
            ],
            'meta' => [
                'current_page' => $organizations->currentPage(),
                'from' => $organizations->firstItem(),
                'last_page' => $organizations->lastPage(),
                'links' => $this->formatLinks($organizations, $baseUrl, $perPage),
                'path' => $baseUrl,
                'per_page' => $organizations->perPage(),
                'to' => $organizations->lastItem(),
                'total' => $organizations->total(),
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    
    private function formatLinks($paginator, $baseUrl, $perPage): array
    {
        $links = [];
        
        $links[] = [
            'url' => $paginator->currentPage() > 1 ? 
                $this->buildUrl($baseUrl, $paginator->currentPage() - 1, $perPage) : null,
            'label' => 'pagination.previous',
            'active' => false,
        ];
        
        for ($i = 1; $i <= $paginator->lastPage(); $i++) {
            $links[] = [
                'url' => $this->buildUrl($baseUrl, $i, $perPage),
                'label' => (string)$i,
                'active' => $i === $paginator->currentPage(),
            ];
        }
        
        $links[] = [
            'url' => $paginator->currentPage() < $paginator->lastPage() ? 
                $this->buildUrl($baseUrl, $paginator->currentPage() + 1, $perPage) : null,
            'label' => 'pagination.next',
            'active' => false,
        ];
        
        return $links;
    }

    private function buildUrl($baseUrl, $page, $perPage): string
    {
        return $baseUrl . '?page=' . $page . '&per_page=' . $perPage;
    }
}