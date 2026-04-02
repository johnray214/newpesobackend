<?php

namespace App\Services;

use App\Models\Jobseeker;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send a modern FCM v1 push notification (OAuth2)
     */
    public static function sendNotification($jobseekerId, $title, $body, $data = [])
    {
        $jobseeker = Jobseeker::find($jobseekerId);
        
        if (!$jobseeker) {
            Log::error("FCM Error: User with ID {$jobseekerId} not found.");
            return false;
        }

        if (!$jobseeker->fcm_token) {
            return false;
        }

        $token = $jobseeker->fcm_token;
        
        try {
            $serviceAccount = storage_path('app/firebase-auth.json');
            if (!file_exists($serviceAccount)) {
                Log::error("FCM Error: firebase-auth.json file is MISSING at: " . $serviceAccount);
                return false;
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/cloud-platform',
                $serviceAccount
            );

            $projectId = json_decode(file_get_contents($serviceAccount))->project_id;
            $accessToken = $credentials->fetchAuthToken()['access_token'];

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($url, [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                ]
            ]);

            if ($response->successful()) {
                Log::info("FCM Success: Message sent to {$jobseeker->email}");
                return true;
            }

            Log::error("FCM Google Error: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }
}
