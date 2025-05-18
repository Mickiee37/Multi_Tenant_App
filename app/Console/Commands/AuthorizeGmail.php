<?php

namespace App\Console\Commands;

use Google_Client;
use Illuminate\Console\Command;

class AuthorizeGmail extends Command
{
    protected $signature = 'gmail:authorize';
    protected $description = 'Authorize Gmail API access';

    public function handle()
    {
        $client = app(Google_Client::class);

        // Get authorization URL
        $authUrl = $client->createAuthUrl();
        $this->info("Open this URL in your browser:\n" . $authUrl);

        // Get authorization code from user
        $authCode = $this->ask('Enter the authorization code');

        // Exchange authorization code for access token
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        if (array_key_exists('error', $accessToken)) {
            $this->error('Error fetching access token: ' . $accessToken['error']);
            return 1;
        }

        // Save the token
        $tokenPath = storage_path('app/token.json');
        file_put_contents($tokenPath, json_encode($accessToken));
        $this->info('Token saved successfully');

        return 0;
    }
} 