<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\WarehouseRepository;
use Illuminate\Http\Request;
use Flash;

class WarehouseController extends AppBaseController
{
    /** @var WarehouseRepository $warehouseRepository*/
    private $warehouseRepository;

    public function __construct(WarehouseRepository $warehouseRepo)
    {
        $this->warehouseRepository = $warehouseRepo;
    }

    /**
     * Display a listing of the Warehouse.
     */
    public function index(Request $request)
    {
        $warehouses = $this->warehouseRepository->paginate(10);

        return view('warehouses.index')
            ->with('warehouses', $warehouses);
    }

    /**
     * Show the form for creating a new Warehouse.
     */
    public function create()
    {
        return view('warehouses.create');
    }

    /**
     * Store a newly created Warehouse in storage.
     */
    public function store(CreateWarehouseRequest $request)
    {
        $input = $request->all();

        $warehouse = $this->warehouseRepository->create($input);

        Flash::success('Warehouse saved successfully.');

        return redirect(route('warehouses.index'));
    }

    /**
     * Display the specified Warehouse.
     */
    public function show($id)
    {
        $warehouse = $this->warehouseRepository->find($id);

        if (empty($warehouse)) {
            Flash::error('Warehouse not found');

            return redirect(route('warehouses.index'));
        }

        return view('warehouses.show')->with('warehouse', $warehouse);
    }

    /**
     * Show the form for editing the specified Warehouse.
     */
    public function edit($id)
    {
        $warehouse = $this->warehouseRepository->find($id);

        if (empty($warehouse)) {
            Flash::error('Warehouse not found');

            return redirect(route('warehouses.index'));
        }

        return view('warehouses.edit')->with('warehouse', $warehouse);
    }

    /**
     * Update the specified Warehouse in storage.
     */
    public function update($id, UpdateWarehouseRequest $request)
    {
        $warehouse = $this->warehouseRepository->find($id);

        if (empty($warehouse)) {
            Flash::error('Warehouse not found');

            return redirect(route('warehouses.index'));
        }

        $warehouse = $this->warehouseRepository->update($request->all(), $id);

        Flash::success('Warehouse updated successfully.');

        return redirect(route('warehouses.index'));
    }

    /**
     * Remove the specified Warehouse from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $warehouse = $this->warehouseRepository->find($id);

        if (empty($warehouse)) {
            Flash::error('Warehouse not found');

            return redirect(route('warehouses.index'));
        }

        $this->warehouseRepository->delete($id);

        Flash::success('Warehouse deleted successfully.');

        return redirect(route('warehouses.index'));
    }
}
