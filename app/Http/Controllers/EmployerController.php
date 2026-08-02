<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EmployerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,App\Models\Employer', ['create', 'store']),
        ];
    }

    public function create(): View
    {
        return view('employer.create');
    }

    public function store(StoreEmployerRequest $request): RedirectResponse
    {
        auth()->user()->employer()->create($request->validated());

        return redirect()->route('jobs.index')
            ->with('success', 'Your employer account was created!');
    }
}
