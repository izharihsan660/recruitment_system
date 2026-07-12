<?php

namespace App\Console\Commands;

use App\Models\EmailIntakeSetting;
use App\Services\GraphMailIntakeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchEmailApplicantIntake extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-intake:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch email lamaran baru dari Microsoft Graph shared mailbox';

    /**
     * Execute the console command.
     */
    public function handle(GraphMailIntakeService $graphMailIntakeService): int
    {
        try {
            $setting = EmailIntakeSetting::query()->where('is_active', true)->first();

            if ($setting?->last_synced_at !== null
                && $setting->last_synced_at->addMinutes($setting->sync_interval_minutes)->isFuture()) {
                $this->info('Email applicant intake belum jatuh tempo untuk sinkronisasi berikutnya.');

                return self::SUCCESS;
            }

            $emails = $graphMailIntakeService->fetchNewMessages();
            $this->info("{$emails->count()} email intake berhasil diproses.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Scheduled email applicant intake gagal.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            $this->error('Email applicant intake gagal. Scheduler akan mencoba lagi pada interval berikutnya.');

            return self::SUCCESS;
        }
    }
}
