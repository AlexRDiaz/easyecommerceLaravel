<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProviderRequest;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\ProviderRepository;
use Illuminate\Http\Request;
use Flash;

class ProviderController extends AppBaseController
{
    /** @var ProviderRepository $providerRepository*/
    private $providerRepository;

    public function __construct(ProviderRepository $providerRepo)
    {
        $this->providerRepository = $providerRepo;
    }

    /**
     * Display a listing of the Provider.
     */
    public function index(Request $request)
    {
        $providers = $this->providerRepository->paginate(10);

        return view('providers.index')
            ->with('providers', $providers);
    }

    /**
     * Show the form for creating a new Provider.
     */
    public function create()
    {
        return view('providers.create');
    }

    /**
     * Store a newly created Provider in storage.
     */
    public function store(CreateProviderRequest $request)
    {
        $input = $request->all();

        $provider = $this->providerRepository->create($input);

        Flash::success('Provider saved successfully.');

        return redirect(route('providers.index'));
    }

    /**
     * Display the specified Provider.
     */
    public function show($id)
    {
        $provider = $this->providerRepository->find($id);

        if (empty($provider)) {
            Flash::error('Provider not found');

            return redirect(route('providers.index'));
        }

        return view('providers.show')->with('provider', $provider);
    }

    /**
     * Show the form for editing the specified Provider.
     */
    public function edit($id)
    {
        $provider = $this->providerRepository->find($id);

        if (empty($provider)) {
            Flash::error('Provider not found');

            return redirect(route('providers.index'));
        }

        return view('providers.edit')->with('provider', $provider);
    }

    /**
     * Update the specified Provider in storage.
     */
    public function update($id, UpdateProviderRequest $request)
    {
        $provider = $this->providerRepository->find($id);

        if (empty($provider)) {
            Flash::error('Provider not found');

            return redirect(route('providers.index'));
        }

        $provider = $this->providerRepository->update($request->all(), $id);

        Flash::success('Provider updated successfully.');

        return redirect(route('providers.index'));
    }

    /**
     * Remove the specified Provider from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $provider = $this->providerRepository->find($id);

        if (empty($provider)) {
            Flash::error('Provider not found');

            return redirect(route('providers.index'));
        }

        $this->providerRepository->delete($id);

        Flash::success('Provider deleted successfully.');

        return redirect(route('providers.index'));
    }
}
