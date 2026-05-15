<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Storage extends Model
{
    use HasFactory;

    protected $table = 'storages';
    protected $guarded =[];

    public static array $types = [
        's3' => 'S3 хранилище',
        'sftp' => 'SFTP хранилище',
        'ftp' => 'FTP хранилище',
    ];

    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
    ];

    public function getData(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function setData(string $key, $value)
    {
        $data = $this->data ??[];
        $data[$key] = $value;
        $this->data = $data;
        return $this;
    }
}
