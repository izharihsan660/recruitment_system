<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyProfileResource;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use App\Services\CompanyProfileService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PortalController extends Controller
{
    public function companyProfile(CompanyProfileService $companyProfileService): CompanyProfileResource
    {
        return new CompanyProfileResource($companyProfileService->current());
    }

    public function jobs(): AnonymousResourceCollection
    {
        return JobPostingResource::collection(JobPosting::query()->with(['entity', 'department'])->where('status', 'open')->latest()->paginate());
    }

    public function job(JobPosting $jobPosting): JobPostingResource
    {
        abort_unless($jobPosting->status === 'open', 404);

        return new JobPostingResource($jobPosting->load(['entity', 'department']));
    }
}
