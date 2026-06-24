<?php

namespace App\Services;

use App\Mail\SimpleTextMail;
use App\Models\InAppNotification;
use App\Models\RecruitmentRequest;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendInApp(User $user, string $type, string $title, string $body, array $data = []): void
    {
        InAppNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function sendEmail(User $user, string $subject, string $body): void
    {
        $smtpSetting = SmtpSetting::query()->where('is_active', true)->first();

        if (! $smtpSetting) {
            Log::warning('SMTP aktif tidak ditemukan, email notification dilewati.', ['user_id' => $user->id]);

            return;
        }

        Config::set('mail.mailers.smtp.host', $smtpSetting->host);
        Config::set('mail.mailers.smtp.port', $smtpSetting->port);
        Config::set('mail.mailers.smtp.username', $smtpSetting->username);
        Config::set('mail.mailers.smtp.password', $smtpSetting->password);
        Config::set('mail.mailers.smtp.encryption', $smtpSetting->encryption);
        Config::set('mail.from.address', $smtpSetting->from_address);
        Config::set('mail.from.name', $smtpSetting->from_name);

        Mail::to($user)->send(new SimpleTextMail($subject, $body));
    }

    public function notifyFpkSubmitted(RecruitmentRequest $fpk, Collection $approvers, Collection $hrUsers): void
    {
        $this->notifyMany($approvers->merge($hrUsers), 'fpk.submitted', 'FPK Menunggu Approval', "FPK {$fpk->position_name} menunggu approval.", $fpk);
    }

    public function notifyFpkApproved(RecruitmentRequest $fpk, User $requester, Collection $hrUsers): void
    {
        $this->notifyMany($hrUsers->push($requester), 'fpk.approved', 'FPK Disetujui', "FPK {$fpk->position_name} sudah disetujui.", $fpk);
    }

    public function notifyFpkRejected(RecruitmentRequest $fpk, User $requester, Collection $hrUsers): void
    {
        $this->notifyMany($hrUsers->push($requester), 'fpk.rejected', 'FPK Ditolak', "FPK {$fpk->position_name} ditolak.", $fpk);
    }

    public function notifyFpkNeedRevision(RecruitmentRequest $fpk, User $requester, Collection $hrUsers): void
    {
        $this->notifyMany($hrUsers->push($requester), 'fpk.need_revision', 'FPK Perlu Revisi', "FPK {$fpk->position_name} perlu direvisi.", $fpk);
    }

    public function hrUsers(): Collection
    {
        return User::role([Roles::HrManager, Roles::HrRecruiter], 'web')->get();
    }

    private function notifyMany(Collection $users, string $type, string $title, string $body, RecruitmentRequest $fpk): void
    {
        $users->unique('id')->each(function (User $user) use ($type, $title, $body, $fpk): void {
            $data = ['recruitment_request_id' => $fpk->id];

            $this->sendInApp($user, $type, $title, $body, $data);
            $this->sendEmail($user, $title, $body);
        });
    }
}
