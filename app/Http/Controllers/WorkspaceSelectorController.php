<?php

namespace App\Http\Controllers;

use App\Support\WorkspaceDestinationResolver;
use Illuminate\Http\Request;

class WorkspaceSelectorController extends Controller
{
    public function __invoke(Request $request, WorkspaceDestinationResolver $resolver)
    {
        $destinations = $resolver->for($request->user());
        $available = array_keys(array_filter($destinations));

        abort_if($available === [], 403);

        if (count($available) === 1) {
            return redirect()->route($available[0] === 'website' ? 'website.dashboard' : 'panel.dashboard');
        }

        return view('workspace.select', [
            'canWebsite' => $destinations['website'],
            'canErp' => $destinations['erp'],
        ]);
    }
}
