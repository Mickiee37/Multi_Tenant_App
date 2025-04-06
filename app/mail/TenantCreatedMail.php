<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $domain;

    public function __construct($user, $password, $domain)
    {
        $this->user = $user;
        $this->password = $password;
        $this->domain = $domain;
    }

    public function build()
    {
        return $this->markdown('emails.tenant.created')
                    ->with([
                        'name' => $this->user->name,
                        'password' => $this->password,
                        'domain' => $this->domain,
                    ]);
    }
}
