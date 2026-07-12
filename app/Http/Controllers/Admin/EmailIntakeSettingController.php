<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmailIntakeSettingRequest;
use App\Models\EmailIntakeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class EmailIntakeSettingController extends Controller
{
    public function update(UpdateEmailIntakeSettingRequest $request): RedirectResponse
    {
        $setting = EmailIntakeSetting::query()->first();
        $data = $request->validated();

        if ($setting === null && blank($data['client_secret'] ?? null)) {
            throw ValidationException::withMessages(['client_secret' => 'Client secret wajib diisi saat membuat konfigurasi pertama.']);
        }

        if (blank($data['client_secret'] ?? null)) {
            unset($data['client_secret']);
        }

        if ($setting === null) {
            $setting = new EmailIntakeSetting;
        }

        $setting->fill($data)->save();

        return redirect()->back()->with('success', 'Konfigurasi Email Applicant Intake berhasil disimpan.');
    }
}
