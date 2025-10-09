<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DadataService;

class DadataController extends Controller
{
    private DadataService $dadataService;

    public function __construct(DadataService $dadataService)
    {
        $this->dadataService = $dadataService;
    }

    public function searchOrganizations(Request $request)
    {
        $query = $request->input('query');
        
        if (!$query || strlen($query) < 3) {
            return response()->json([]);
        }

        $result = $this->dadataService->searchOrganizations($query);
        
        return response()->json($result);
    }
}