<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnseignantPasswordMail extends Mailable
{
    use SerializesModels;

    public $password;
    public $teacher;

    public function __construct($teacher, $password)
    {
        $this->teacher = $teacher;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Vos identifiants - Classe Inclusion')
                    ->view('emails.teacher_password');
    }
}
