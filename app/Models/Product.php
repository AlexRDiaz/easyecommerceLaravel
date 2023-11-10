<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $table = 'products';

    public $fillable = [
        'product_name',
        'stock',
        'features',
        'price',
        'url_img',
        'warehouse_id'
    ];

    protected $casts = [
        'product_name' => 'string',
        'features' => 'string',
        'price' => 'decimal:2',
        'url_img' => 'string'
    ];

    public static array $rules = [
        'product_name' => 'nullable|string|max:70',
        'stock' => 'nullable',
        'features' => 'nullable|string',
        'price' => 'nullable|numeric',
        'url_img' => 'nullable|string|max:70',
        'warehouse_id' => 'nullable',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }
}
