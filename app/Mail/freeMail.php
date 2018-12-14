<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class freeMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $content;
    

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function build()
    {
        return $this->html($content);
    }
}
