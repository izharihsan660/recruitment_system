<?php

namespace App\Notifications;

use App\Models\RecruitmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FpkStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly RecruitmentRequest $fpk) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->fpk->loadMissing('approvalRecords');

        return match ($this->fpk->status) {
            'approved' => (new MailMessage)
                ->subject('FPK Anda Telah Disetujui - '.$this->fpk->position_name)
                ->greeting('Halo,')
                ->line('FPK untuk posisi '.$this->fpk->position_name.' telah disetujui seluruh approver.')
                ->line('FPK siap dilanjutkan ke proses Job Posting.')
                ->action('Lihat Detail FPK', route('fpk.show', $this->fpk)),
            'rejected' => (new MailMessage)
                ->subject('FPK Anda Ditolak - '.$this->fpk->position_name)
                ->greeting('Halo,')
                ->line('FPK untuk posisi '.$this->fpk->position_name.' ditolak.')
                ->line('Alasan: '.$this->decisionComment('rejected'))
                ->action('Lihat Detail FPK', route('fpk.show', $this->fpk)),
            'need_revision' => (new MailMessage)
                ->subject('FPK Memerlukan Revisi - '.$this->fpk->position_name)
                ->greeting('Halo,')
                ->line('FPK untuk posisi '.$this->fpk->position_name.' memerlukan revisi.')
                ->line('Catatan revisi: '.$this->decisionComment('need_revision'))
                ->action('Revisi FPK', route('fpk.edit', $this->fpk)),
            default => throw new \LogicException('Status FPK tidak mendukung notifikasi perubahan status.'),
        };
    }

    private function decisionComment(string $action): string
    {
        return (string) $this->fpk->approvalRecords->firstWhere('action', $action)?->comment;
    }
}
