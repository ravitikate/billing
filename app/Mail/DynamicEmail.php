<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable; 
use Illuminate\Queue\SerializesModels;

class DynamicEmail extends Mailable
{
    use Queueable, SerializesModels;

    public array $emailData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Compose full HTML message
        $fullMessage =  "<p>{$this->emailData['message']}</p>";

        return $this->subject($this->emailData['subject'])
                    ->html($fullMessage);
    }
}
