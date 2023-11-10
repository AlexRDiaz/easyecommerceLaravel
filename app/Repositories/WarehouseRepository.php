<?php

namespace App\Repositories;

use App\Models\Warehouse;
use App\Repositories\BaseRepository;

class WarehouseRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'branch_name',
        'address',
        'reference',
        'description',
        'provider_id'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Warehouse::class;
    }
}
