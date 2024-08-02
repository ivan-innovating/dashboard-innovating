<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuperAdminMail extends Mailable
{
    use Queueable, SerializesModels;
    public $correo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($correo)
    {
        //
        $this->correo = $correo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject($this->correo->asunto_mail);
        return $this->markdown('emails.superadminmail');
    }
}
