<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestionedNovelty extends Model
{
    public $table = 'gestioned_novelties';

    public $fillable = [
        'try',
        'comment',
        'updated_by',
        'created_by'
    ];

    protected $casts = [
        'try' => 'string',
        'comment' => 'string'
    ];

    public static array $rules = [
        'try' => 'nullable|string|max:45',
        'comment' => 'nullable|string|max:200',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'updated_by' => 'nullable',
        'created_by' => 'nullable'
    ];

    
}
