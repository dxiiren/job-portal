<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobApplicationRequest;
use App\Models\Job;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class JobApplicationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:applyJob,job', ['create', 'store']),
        ];
    }

    public function create(Job $job): View
    {
        return view('job_application.create', ['job' => $job]);
    }

    public function store(StoreJobApplicationRequest $request, Job $job)
    {
        $file = $request->file('cv');
        $path = $file->store('cvs', 'local');

        $job->jobApplications()->create([
            'user_id' => $request->user()->id,
            'expected_salary' => $request->safe()->input('expected_salary'),
            'cv_path' => $path,
        ]);

        return redirect()->route('jobs.show', $job)->with('success', 'Job application submitted.');
    }
}
