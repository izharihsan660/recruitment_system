<?php

namespace App\Notifications;

use App\Models\UserInterview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInterviewScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly UserInterview $interview) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->interview->loadMissing(['application.candidate', 'application.jobPosting']);

        return (new MailMessage)
            ->subject('Jadwal Interview User')
            ->greeting('Halo,')
            ->line('Interview user telah dijadwalkan.')
            ->line('Kandidat: '.$this->interview->application->candidate->name)
            ->line('Posisi: '.$this->interview->application->jobPosting->position_name)
            ->line('Waktu: '.$this->interview->scheduled_at->format('d M Y H:i'))
            ->line('Lokasi: '.$this->interview->location);
    }
}
