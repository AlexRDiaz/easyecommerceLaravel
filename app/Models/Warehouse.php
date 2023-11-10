<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    public $table = 'warehouses';

    public $fillable = [
        'branch_name',
        'address',
        'reference',
        'description',
        'provider_id'
    ];

    protected $casts = [
        'branch_name' => 'string',
        'address' => 'string',
        'reference' => 'string',
        'description' => 'string'
    ];

    public static array $rules = [
        'branch_name' => 'nullable|string|max:70',
        'address' => 'nullable|string|max:70',
        'reference' => 'nullable|string|max:70',
        'description' => 'nullable|string|max:65535',
        'provider_id' => 'nullable',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public function provider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Supplier::class, 'provider_id');
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Product::class, 'warehouse_id');
    }
}
