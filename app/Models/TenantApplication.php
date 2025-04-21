<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class TenantApplication extends Model
{
    use HasFactory, Notifiable;

    protected $connection = 'mysql'; // Always use the main database connection

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'domain',
        'database_name',
        'status',
        'rejection_reason',
    ];

    public function routeNotificationForMail()
    {
        return $this->email;
    }
} 