<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        try {
            $client = app(Client::class);
            $client->setRedirectUri(url('/oauth/callback'));
            $client->addScope(Gmail::GMAIL_SEND);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            
            $authUrl = $client->createAuthUrl();
            Log::info('Created Google auth URL', ['url' => $authUrl]);
            
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Error in redirect', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect('/admin/tenant-applications')
                ->with('error', 'Failed to start Google authentication: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            $client = app(Client::class);
            $client->setRedirectUri(url('/oauth/callback'));
            
            if ($request->has('code')) {
                Log::info('Received authorization code from Google');
                
                $token = $client->fetchAccessTokenWithAuthCode($request->code);
                Log::info('Fetched token from Google', ['token' => $token]);
                
                if (!isset($token['error'])) {
                    // Create directories if they don't exist
                    $googlePath = storage_path('app/google');
                    if (!file_exists($googlePath)) {
                        mkdir($googlePath, 0755, true);
                        Log::info('Created google directory', ['path' => $googlePath]);
                    }

                    // Save token file
                    $tokenPath = $googlePath . '/token.json';
                    $tokenContent = json_encode($token, JSON_PRETTY_PRINT);
                    $bytesWritten = file_put_contents($tokenPath, $tokenContent);
                    
                    Log::info('Attempted to save token', [
                        'token_path' => $tokenPath,
                        'bytes_written' => $bytesWritten,
                        'file_exists' => file_exists($tokenPath),
                        'file_permissions' => substr(sprintf('%o', fileperms($tokenPath)), -4),
                        'directory_permissions' => substr(sprintf('%o', fileperms($googlePath)), -4)
                    ]);
                    
                    // Verify token was saved correctly
                    if (file_exists($tokenPath)) {
                        $savedToken = file_get_contents($tokenPath);
                        if ($savedToken === $tokenContent) {
                            Log::info('Token saved and verified successfully');
                            return redirect('/admin/tenant-applications')
                                ->with('success', 'Gmail API authenticated successfully! Token has been saved.');
                        } else {
                            throw new \Exception('Token verification failed - content mismatch');
                        }
                    } else {
                        throw new \Exception('Failed to save token file to: ' . $tokenPath);
                    }
                } else {
                    throw new \Exception('Error in token response: ' . ($token['error'] ?? 'Unknown error'));
                }
            } else {
                throw new \Exception('No authorization code present in callback');
            }
        } catch (\Exception $e) {
            Log::error('Google Auth Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all()
            ]);
            
            return redirect('/admin/tenant-applications')
                ->with('error', 'Failed to authenticate with Gmail API: ' . $e->getMessage());
        }
    }
} 