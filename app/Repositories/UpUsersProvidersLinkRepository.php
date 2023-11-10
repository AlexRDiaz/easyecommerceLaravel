<?php

namespace App\Repositories;

use App\Models\UpUsersProvidersLink;
use App\Repositories\BaseRepository;

class UpUsersProvidersLinkRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'provider_id',
        'up_user_id'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return UpUsersProvidersLink::class;
    }
}
