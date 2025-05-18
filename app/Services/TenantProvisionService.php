<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantCreatedMail;

class TenantProvisionService
{
    public function registerTenant($request)
    {
        // Generate password and username
        $password = Str::random(10);
        $username = strtolower(Str::slug($request->first_name . $request->last_name));
        $domain = $username . '.yourapp.test';

        // Create user
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'username' => $username,
            'password' => Hash::make($password),
        ]);

        // Create tenant record
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'domain' => $domain,
        ]);

        // Create Tenant DB and Run Migrations
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'tenants:setup',
            '--tenant' => $tenant->id,
        ]);

        // Send email to user
        Mail::to($user->email)->send(new TenantCreatedMail($user, $password, $domain));
    }
}

