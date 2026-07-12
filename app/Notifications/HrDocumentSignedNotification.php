<?php

namespace App\Notifications;

use App\Models\OfferingLetter;
use App\Models\PkwtContract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrDocumentSignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly OfferingLetter|PkwtContract $document) {}

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
        $this->document->loadMissing(['application.candidate']);
        $candidateName = $this->document->application->candidate->name;

        if ($this->document instanceof OfferingLetter) {
            return (new MailMessage)
                ->subject('Offering Letter Signed - '.$candidateName)
                ->greeting('Halo,')
                ->line('Offering Letter '.$candidateName.' telah ditandatangani kedua pihak.')
                ->line('Proses siap dilanjutkan ke MCU/SIMPER bila diperlukan atau ke Hiring Decision.')
                ->action('Lihat Offering Letter', route('offering.show', $this->document->application));
        }

        return (new MailMessage)
            ->subject('PKWT Signed - '.$candidateName)
            ->greeting('Halo,')
            ->line('PKWT '.$candidateName.' telah ditandatangani kedua pihak dan kandidat otomatis berstatus Hired.')
            ->line('Proses siap dilanjutkan ke Active Employee setelah PKWT diarsipkan.')
            ->action('Lihat PKWT', route('pkwt.show', $this->document->application));
    }
}
