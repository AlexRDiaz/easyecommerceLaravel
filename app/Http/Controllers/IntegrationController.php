<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateIntegrationRequest;
use App\Http\Requests\UpdateIntegrationRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\IntegrationRepository;
use Illuminate\Http\Request;
use Flash;

class IntegrationController extends AppBaseController
{
    /** @var IntegrationRepository $integrationRepository*/
    private $integrationRepository;

    public function __construct(IntegrationRepository $integrationRepo)
    {
        $this->integrationRepository = $integrationRepo;
    }

    /**
     * Display a listing of the Integration.
     */
    public function index(Request $request)
    {
        $integrations = $this->integrationRepository->paginate(10);

        return view('integrations.index')
            ->with('integrations', $integrations);
    }

    /**
     * Show the form for creating a new Integration.
     */
    public function create()
    {
        return view('integrations.create');
    }

    /**
     * Store a newly created Integration in storage.
     */
    public function store(CreateIntegrationRequest $request)
    {
        $input = $request->all();

        $integration = $this->integrationRepository->create($input);

        Flash::success('Integration saved successfully.');

        return redirect(route('integrations.index'));
    }

    /**
     * Display the specified Integration.
     */
    public function show($id)
    {
        $integration = $this->integrationRepository->find($id);

        if (empty($integration)) {
            Flash::error('Integration not found');

            return redirect(route('integrations.index'));
        }

        return view('integrations.show')->with('integration', $integration);
    }

    /**
     * Show the form for editing the specified Integration.
     */
    public function edit($id)
    {
        $integration = $this->integrationRepository->find($id);

        if (empty($integration)) {
            Flash::error('Integration not found');

            return redirect(route('integrations.index'));
        }

        return view('integrations.edit')->with('integration', $integration);
    }

    /**
     * Update the specified Integration in storage.
     */
    public function update($id, UpdateIntegrationRequest $request)
    {
        $integration = $this->integrationRepository->find($id);

        if (empty($integration)) {
            Flash::error('Integration not found');

            return redirect(route('integrations.index'));
        }

        $integration = $this->integrationRepository->update($request->all(), $id);

        Flash::success('Integration updated successfully.');

        return redirect(route('integrations.index'));
    }

    /**
     * Remove the specified Integration from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $integration = $this->integrationRepository->find($id);

        if (empty($integration)) {
            Flash::error('Integration not found');

            return redirect(route('integrations.index'));
        }

        $this->integrationRepository->delete($id);

        Flash::success('Integration deleted successfully.');

        return redirect(route('integrations.index'));
    }
}
