<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $fillable = ['name', 'path', 'size', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'size' => 'integer',
    ];

    public $timestamps = false;
}
