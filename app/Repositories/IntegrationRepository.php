<?php

namespace App\Repositories;

use App\Models\Integration;
use App\Repositories\BaseRepository;

class IntegrationRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'store_url',
        'user_id',
        'token'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Integration::class;
    }
}
