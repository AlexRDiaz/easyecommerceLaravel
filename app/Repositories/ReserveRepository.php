<?php

namespace App\Repositories;

use App\Models\Reserve;
use App\Repositories\BaseRepository;

class ReserveRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'product_id',
        'sku',
        'stock'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Reserve::class;
    }
}
