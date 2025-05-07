<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantApplicationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $domain;
    protected $username;
    protected $password;

    public function __construct($domain, $username, $password)
    {
        $this->domain = $domain;
        $this->username = $username;
        $this->password = $password;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $domain = config('app.url');
        if (str_contains($domain, 'localhost')) {
            $domain = 'http://'. $this->domain .'.localhost:8000';
        } else {
            $domain = 'http://'. $this->domain .'.'. config('tenancy.central_domains')[0];
        }

        return (new MailMessage)
            ->subject('Your Tenant Application has been Approved!')
            ->greeting('Congratulations!')
            ->line('Your tenant application has been approved. Below are your login credentials:')
            ->line('Domain: ' . $domain)
            ->line('Username: ' . $this->username)
            ->line('Password: ' . $this->password)
            ->line('Please change your password after your first login.')
            ->action('Access Your Domain', $domain . '/login')
            ->line('Thank you for using our application!');
    }
} 