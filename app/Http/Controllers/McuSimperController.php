<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleMcuRequest;
use App\Http\Requests\ScheduleSimperRequest;
use App\Http\Requests\SubmitMcuResultRequest;
use App\Http\Requests\SubmitSimperResultRequest;
use App\Models\Application;
use App\Services\McuSimperService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class McuSimperController extends Controller
{
    public function __construct(private readonly McuSimperService $mcuSimperService) {}

    public function show(Application $application): Response
    {
        $record = $application->mcuSimperRecord ?: null;

        return Inertia::render('Pipeline/McuSimper', ['application' => $application->load(['candidate', 'jobPosting.department']), 'record' => $record, 'canProceed' => $record ? $this->mcuSimperService->checkCanProceed($record) : false]);
    }

    public function store(Application $application): RedirectResponse
    {
        $this->mcuSimperService->create($application, request()->user());

        return back()->with('success', 'Record MCU/SIMPER berhasil dibuat.');
    }

    public function scheduleMcu(ScheduleMcuRequest $request, Application $application): RedirectResponse
    {
        $this->mcuSimperService->scheduleMcu($this->mcuSimperService->create($application, $request->user()), $request->validated(), $request->user());

        return back()->with('success', 'Jadwal MCU berhasil dikirim.');
    }

    public function scheduleSimper(ScheduleSimperRequest $request, Application $application): RedirectResponse
    {
        $this->mcuSimperService->scheduleSimper($this->mcuSimperService->create($application, $request->user()), $request->validated(), $request->user());

        return back()->with('success', 'Jadwal SIMPER berhasil dikirim.');
    }

    public function resultMcu(SubmitMcuResultRequest $request, Application $application): RedirectResponse
    {
        $this->mcuSimperService->submitMcuResult($this->mcuSimperService->create($application, $request->user()), $request->validated(), $request->user());

        return back()->with('success', 'Hasil MCU berhasil diupload.');
    }

    public function resultSimper(SubmitSimperResultRequest $request, Application $application): RedirectResponse
    {
        $this->mcuSimperService->submitSimperResult($this->mcuSimperService->create($application, $request->user()), $request->validated(), $request->user());

        return back()->with('success', 'Hasil SIMPER berhasil diupload.');
    }

    public function proceed(Application $application): RedirectResponse
    {
        $this->mcuSimperService->proceedToHiringDecision($application->mcuSimperRecord()->firstOrFail(), request()->user());

        return redirect('/pipeline')->with('success', 'Aplikasi lanjut ke Hiring Decision.');
    }
}
