<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomFieldController extends Controller
{
    public function validate(Request $request)
    {
        $rule = $request->input('rule');
        $table = $request->input('table');
        $column = $request->input('column');
        $value = $request->input('value');
        $modelId = $request->input('modelId');
        $normalize = $request->input('normalize');

        $normalizedValue = $this->normalizeValue($value, $normalize);

        if ($rule === 'unique') {
            $query = DB::table($table);
            if ($normalize !== null) {
                $query->whereRaw(
                    $this->normalizedColumnExpression($column, $normalize) . ' = ?',
                    [$normalizedValue],
                );
            } else {
                $query->where($column, $normalizedValue);
            }

            if ($modelId !== null && $modelId !== '') {
                $query->where('id', '!=', $modelId);
            }

            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            return response()->json(['is_valid' => !$query->exists()]);
        }

        if ($rule === 'exists') {
            $isValid = DB::table($table)->where($column, $normalizedValue)->exists();

            return response()->json(['is_valid' => $isValid]);
        }

        return response()->json(['is_valid' => true]);
    }

    protected function normalizeValue(?string $value, ?string $normalize): ?string
    {
        if ($value === null || $normalize === null) {
            return $value;
        }

        return match ($normalize) {
            'license_plate' => mb_strtoupper(preg_replace('/\s+/', '', $value)),
            default => $value,
        };
    }

    protected function normalizedColumnExpression(string $column, string $normalize): string
    {
        $safeColumn = '"' . preg_replace('/[^A-Za-z0-9_]/', '', $column) . '"';

        return match ($normalize) {
            'license_plate' => "UPPER(REPLACE($safeColumn, ' ', ''))",
            default => $safeColumn,
        };
    }
}
