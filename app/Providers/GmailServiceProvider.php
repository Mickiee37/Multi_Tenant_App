<?php

namespace App\Providers;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class GmailServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            $client = new Client();
            
            $credentialsPath = storage_path('app/google/client_secret_1094970163345-3k5febu0tvtd5dq5208su282vaiccdbh.apps.googleusercontent.com.json');
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception('Gmail credentials file not found at: ' . $credentialsPath);
            }

            $client->setAuthConfig($credentialsPath);
            $client->addScope(Gmail::GMAIL_SEND);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            
            // Load previously authorized token from a file, if it exists.
            $tokenPath = storage_path('app/google/token.json');
            if (file_exists($tokenPath)) {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $client->setAccessToken($accessToken);
            }

            return $client;
        });

        $this->app->singleton(Gmail::class, function ($app) {
            return new Gmail($app->make(Client::class));
        });
    }
} 