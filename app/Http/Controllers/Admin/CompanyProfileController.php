<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Http\Requests\UploadCompanyImageRequest;
use App\Models\CompanyProfile;
use App\Services\CompanyProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CompanyProfileController extends Controller
{
    public function __construct(private readonly CompanyProfileService $companyProfileService) {}

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $this->companyProfileService->update($request->validated());

        return redirect()->back()->with('success', 'Profil perusahaan berhasil diperbarui.');
    }

    public function heroImage(UploadCompanyImageRequest $request): RedirectResponse
    {
        $this->companyProfileService->replaceHeroImage($request->file('image'));

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function gallery(UploadCompanyImageRequest $request): RedirectResponse
    {
        $this->companyProfileService->addGalleryImage($request->file('image'));

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function deleteGallery(int $index): RedirectResponse
    {
        Gate::authorize('update', CompanyProfile::class);

        $this->companyProfileService->deleteGalleryImage($index);

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }
}
