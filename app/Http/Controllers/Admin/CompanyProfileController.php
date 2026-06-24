<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Http\Requests\UploadCompanyImageRequest;
use App\Http\Resources\CompanyProfileResource;
use App\Models\CompanyProfile;
use App\Services\CompanyProfileService;
use Illuminate\Support\Facades\Gate;

class CompanyProfileController extends Controller
{
    public function __construct(private readonly CompanyProfileService $companyProfileService) {}

    public function update(UpdateCompanyProfileRequest $request): CompanyProfileResource
    {
        return new CompanyProfileResource($this->companyProfileService->update($request->validated()));
    }

    public function heroImage(UploadCompanyImageRequest $request): CompanyProfileResource
    {
        return new CompanyProfileResource($this->companyProfileService->replaceHeroImage($request->file('image')));
    }

    public function gallery(UploadCompanyImageRequest $request): CompanyProfileResource
    {
        return new CompanyProfileResource($this->companyProfileService->addGalleryImage($request->file('image')));
    }

    public function deleteGallery(int $index): CompanyProfileResource
    {
        Gate::authorize('update', CompanyProfile::class);

        return new CompanyProfileResource($this->companyProfileService->deleteGalleryImage($index));
    }
}
