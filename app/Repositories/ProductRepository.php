<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\BaseRepository;

class ProductRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'product_name',
        'stock',
        'features',
        'price',
        'url_img',
        'warehouse_id'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Product::class;
    }
}
