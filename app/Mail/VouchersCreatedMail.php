<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VouchersCreatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public array $success;
    public array $failed;
    public User $user;

    public function __construct(array $success, array $failed, User $user)
    {
        $this->success = $success;
        $this->failed = $failed;
        $this->user = $user;
    }

    public function build(): self
    {
        return $this->view('emails.vouchers')
            ->subject('Subida de comprobantes')
            ->with([
                'success' => $this->success,
                'failed' => $this->failed,
                'user' => $this->user
            ]);
    }
}
