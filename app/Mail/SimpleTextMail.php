<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SimpleTextMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $subjectText,
        public readonly string $bodyText,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectText)
            ->text('mail.simple-text')
            ->with(['bodyText' => $this->bodyText]);
    }
}
