<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportTask extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_CSV = 'csv';
    public const FORMAT_ZIP = 'zip';

    protected $guarded = [];

    protected $casts = [
        'query' => 'array',
    ];
}
