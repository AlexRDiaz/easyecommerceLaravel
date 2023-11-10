<?php

namespace App\Policies;

use App\Models\UpUser;
use App\Models\Warehouse;
use Illuminate\Auth\Access\Response;

class WarehousePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(UpUser $upUser): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(UpUser $upUser, Warehouse $warehouse): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(UpUser $upUser): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(UpUser $upUser, Warehouse $warehouse): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(UpUser $upUser, Warehouse $warehouse): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(UpUser $upUser, Warehouse $warehouse): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(UpUser $upUser, Warehouse $warehouse): bool
    {
        //
    }
}
