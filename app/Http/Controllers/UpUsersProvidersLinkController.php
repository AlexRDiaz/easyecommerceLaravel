<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUpUsersProvidersLinkRequest;
use App\Http\Requests\UpdateUpUsersProvidersLinkRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\UpUsersProvidersLinkRepository;
use Illuminate\Http\Request;
use Flash;

class UpUsersProvidersLinkController extends AppBaseController
{
    /** @var UpUsersProvidersLinkRepository $upUsersProvidersLinkRepository*/
    private $upUsersProvidersLinkRepository;

    public function __construct(UpUsersProvidersLinkRepository $upUsersProvidersLinkRepo)
    {
        $this->upUsersProvidersLinkRepository = $upUsersProvidersLinkRepo;
    }

    /**
     * Display a listing of the UpUsersProvidersLink.
     */
    public function index(Request $request)
    {
        $upUsersProvidersLinks = $this->upUsersProvidersLinkRepository->paginate(10);

        return view('up_users_providers_links.index')
            ->with('upUsersProvidersLinks', $upUsersProvidersLinks);
    }

    /**
     * Show the form for creating a new UpUsersProvidersLink.
     */
    public function create()
    {
        return view('up_users_providers_links.create');
    }

    /**
     * Store a newly created UpUsersProvidersLink in storage.
     */
    public function store(CreateUpUsersProvidersLinkRequest $request)
    {
        $input = $request->all();

        $upUsersProvidersLink = $this->upUsersProvidersLinkRepository->create($input);

        Flash::success('Up Users Providers Link saved successfully.');

        return redirect(route('upUsersProvidersLinks.index'));
    }

    /**
     * Display the specified UpUsersProvidersLink.
     */
    public function show($id)
    {
        $upUsersProvidersLink = $this->upUsersProvidersLinkRepository->find($id);

        if (empty($upUsersProvidersLink)) {
            Flash::error('Up Users Providers Link not found');

            return redirect(route('upUsersProvidersLinks.index'));
        }

        return view('up_users_providers_links.show')->with('upUsersProvidersLink', $upUsersProvidersLink);
    }

    /**
     * Show the form for editing the specified UpUsersProvidersLink.
     */
    public function edit($id)
    {
        $upUsersProvidersLink = $this->upUsersProvidersLinkRepository->find($id);

        if (empty($upUsersProvidersLink)) {
            Flash::error('Up Users Providers Link not found');

            return redirect(route('upUsersProvidersLinks.index'));
        }

        return view('up_users_providers_links.edit')->with('upUsersProvidersLink', $upUsersProvidersLink);
    }

    /**
     * Update the specified UpUsersProvidersLink in storage.
     */
    public function update($id, UpdateUpUsersProvidersLinkRequest $request)
    {
        $upUsersProvidersLink = $this->upUsersProvidersLinkRepository->find($id);

        if (empty($upUsersProvidersLink)) {
            Flash::error('Up Users Providers Link not found');

            return redirect(route('upUsersProvidersLinks.index'));
        }

        $upUsersProvidersLink = $this->upUsersProvidersLinkRepository->update($request->all(), $id);

        Flash::success('Up Users Providers Link updated successfully.');

        return redirect(route('upUsersProvidersLinks.index'));
    }

    /**
     * Remove the specified UpUsersProvidersLink from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $upUsersProvidersLink = $this->upUsersProvidersLinkRepository->find($id);

        if (empty($upUsersProvidersLink)) {
            Flash::error('Up Users Providers Link not found');

            return redirect(route('upUsersProvidersLinks.index'));
        }

        $this->upUsersProvidersLinkRepository->delete($id);

        Flash::success('Up Users Providers Link deleted successfully.');

        return redirect(route('upUsersProvidersLinks.index'));
    }
}
