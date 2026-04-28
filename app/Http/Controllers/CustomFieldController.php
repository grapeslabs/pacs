<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class CustomFieldController extends Controller
{
    public function validate(Request $request)
    {
        $rule = $request->input('rule');
        $table = $request->input('table');
        $column = $request->input('column');
        $value = $request->input('value');
        $modelId = $request->input('modelId');

        if ($rule === 'unique') {
            $query = DB::table($table)->where($column, $value);

            if ($modelId !== null && $modelId !== '') {
                $query->where('id', '!=', $modelId);
            }

            return response()->json(['is_valid' => !$query->exists()]);
        }

        if ($rule === 'exists') {
            $isValid = DB::table($table)->where($column, $value)->exists();

            return response()->json(['is_valid' => $isValid]);
        }

        return response()->json(['is_valid' => true]);
    }
}
