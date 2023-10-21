<?php

namespace App\Repositories;

use App\Models\TransportStats;
use App\Repositories\BaseRepository;

class transportStatsRepository
{
    protected $fieldSearchable = [
        
    ];

    public function create(TransportStats $transportstat)
    {
        return $transportstat->save();
    }
    
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return TransportStats::class;
    }
}
