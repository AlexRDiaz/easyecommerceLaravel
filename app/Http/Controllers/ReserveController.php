<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReserveRequest;
use App\Http\Requests\UpdateReserveRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\ReserveRepository;
use Illuminate\Http\Request;
use Flash;

class ReserveController extends AppBaseController
{
    /** @var ReserveRepository $reserveRepository*/
    private $reserveRepository;

    public function __construct(ReserveRepository $reserveRepo)
    {
        $this->reserveRepository = $reserveRepo;
    }

    /**
     * Display a listing of the Reserve.
     */
    public function index(Request $request)
    {
        $reserves = $this->reserveRepository->paginate(10);

        return view('reserves.index')
            ->with('reserves', $reserves);
    }

    /**
     * Show the form for creating a new Reserve.
     */
    public function create()
    {
        return view('reserves.create');
    }

    /**
     * Store a newly created Reserve in storage.
     */
    public function store(CreateReserveRequest $request)
    {
        $input = $request->all();

        $reserve = $this->reserveRepository->create($input);

        Flash::success('Reserve saved successfully.');

        return redirect(route('reserves.index'));
    }

    /**
     * Display the specified Reserve.
     */
    public function show($id)
    {
        $reserve = $this->reserveRepository->find($id);

        if (empty($reserve)) {
            Flash::error('Reserve not found');

            return redirect(route('reserves.index'));
        }

        return view('reserves.show')->with('reserve', $reserve);
    }

    /**
     * Show the form for editing the specified Reserve.
     */
    public function edit($id)
    {
        $reserve = $this->reserveRepository->find($id);

        if (empty($reserve)) {
            Flash::error('Reserve not found');

            return redirect(route('reserves.index'));
        }

        return view('reserves.edit')->with('reserve', $reserve);
    }

    /**
     * Update the specified Reserve in storage.
     */
    public function update($id, UpdateReserveRequest $request)
    {
        $reserve = $this->reserveRepository->find($id);

        if (empty($reserve)) {
            Flash::error('Reserve not found');

            return redirect(route('reserves.index'));
        }

        $reserve = $this->reserveRepository->update($request->all(), $id);

        Flash::success('Reserve updated successfully.');

        return redirect(route('reserves.index'));
    }

    /**
     * Remove the specified Reserve from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $reserve = $this->reserveRepository->find($id);

        if (empty($reserve)) {
            Flash::error('Reserve not found');

            return redirect(route('reserves.index'));
        }

        $this->reserveRepository->delete($id);

        Flash::success('Reserve deleted successfully.');

        return redirect(route('reserves.index'));
    }
}
