<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AceptaPriorizar extends Mailable
{
    use Queueable, SerializesModels;

    public $priorizar;
    public $data;
    public $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($priorizar, $data, $message)
    {
        //
        $this->priorizar = $priorizar;
        $this->data = $data;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject("Hemos resuelto tu petición de priorización");
        return $this->markdown('emails.aceptapriorizar');
    }
}
