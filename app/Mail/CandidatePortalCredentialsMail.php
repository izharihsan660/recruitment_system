<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CandidatePortalCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Candidate $candidate, public string $temporaryPassword) {}

    public function build(): static
    {
        return $this
            ->subject('Akses Portal Kandidat')
            ->html('<p>Halo '.$this->candidate->name.',</p><p>Akun portal kandidat Anda telah dibuat.</p><p>Email: '.$this->candidate->email.'<br>Password sementara: '.$this->temporaryPassword.'</p><p>Silakan login dan ganti password setelah masuk.</p>');
    }
}
