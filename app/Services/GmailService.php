<?php

namespace App\Services;

use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GmailService
{
    protected $service;
    protected $credentialsFile = 'google/client_secret_1094970163345-3k5febu0tvtd5dq5208su282vaiccdbh.apps.googleusercontent.com.json';
    protected $tokenFile = 'google/token.json';

    public function __construct(Gmail $service)
    {
        $this->service = $service;
    }

    public function isConfigured(): bool
    {
        $credentialsPath = storage_path('app/' . $this->credentialsFile);
        $tokenPath = storage_path('app/' . $this->tokenFile);

        Log::info('Checking Gmail configuration:', [
            'credentials_path' => $credentialsPath,
            'credentials_exists' => file_exists($credentialsPath),
            'token_path' => $tokenPath,
            'token_exists' => file_exists($tokenPath)
        ]);

        if (!file_exists($credentialsPath)) {
            throw new \Exception('Gmail credentials file not found at: ' . $credentialsPath);
        }

        if (!file_exists($tokenPath)) {
            throw new \Exception('Gmail token file not found. Please authenticate at /oauth/redirect first.');
        }

        return true;
    }

    public function sendEmail($to, $subject, $messageText)
    {
        try {
            $this->isConfigured();

            Log::info('Attempting to send email', [
                'to' => $to,
                'subject' => $subject
            ]);

            $message = $this->createMessage($to, $subject, $messageText);
            
            $result = $this->service->users_messages->send('me', $message);
            
            Log::info('Email sent successfully', [
                'message_id' => $result->getId()
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Gmail API Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function createMessage($to, $subject, $messageText)
    {
        try {
            $rawMessageString = "To: {$to}\r\n";
            $rawMessageString .= "Subject: {$subject}\r\n";
            $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n";
            $rawMessageString .= "\r\n" . $messageText;

            $rawMessage = strtr(base64_encode($rawMessageString), array('+' => '-', '/' => '_'));
            
            $message = new Message();
            $message->setRaw($rawMessage);
            
            return $message;
        } catch (\Exception $e) {
            Log::error('Error creating email message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 