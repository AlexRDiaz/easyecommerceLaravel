<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserve extends Model
{
    public $table = 'reserves';

    public $fillable = [
        'product_id',
        'sku',
        'stock'
    ];

    protected $casts = [
        'sku' => 'string'
    ];

    public static array $rules = [
        'product_id' => 'required',
        'sku' => 'required|string|max:255',
        'stock' => 'required'
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }
}
