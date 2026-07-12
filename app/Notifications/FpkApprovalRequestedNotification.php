<?php

namespace App\Notifications;

use App\Models\RecruitmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FpkApprovalRequestedNotification extends Notification
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
        $this->fpk->loadMissing(['department', 'requester']);

        return (new MailMessage)
            ->subject("Permintaan Approval FPK - {$this->fpk->position_name} ({$this->fpk->department->name})")
            ->greeting('Halo,')
            ->line('FPK berikut memerlukan keputusan approval Anda.')
            ->line('Pemohon: '.$this->fpk->requester->name)
            ->line('Departemen: '.$this->fpk->department->name)
            ->line('Posisi: '.$this->fpk->position_name)
            ->line('Jumlah karyawan: '.$this->fpk->headcount)
            ->line('Alasan kebutuhan: '.$this->fpk->reason_notes)
            ->action('Lihat Detail FPK', route('fpk.show', $this->fpk));
    }
}
