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
            $jsonContent = env('FIREBASE_AUTH_JSON');
            $credentialsData = null;
            $projectId = null;

            if ($jsonContent) {
                // Priority 1: Railway Secrets
                $credentialsData = json_decode($jsonContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error("FCM Error: FIREBASE_AUTH_JSON environment variable is not valid JSON.");
                    return false;
                }
                $projectId = $credentialsData['project_id'] ?? null;
            } else {
                // Priority 2: Local storage file
                $serviceAccountPath = storage_path('app/firebase-auth.json');
                if (!file_exists($serviceAccountPath)) {
                    Log::error("FCM Error: No Firebase secret found in Env or storage/app/.");
                    return false;
                }
                $credentialsData = $serviceAccountPath;
                
                // Get project_id for Local File
                $fileContent = json_decode(file_get_contents($serviceAccountPath), true);
                $projectId = $fileContent['project_id'] ?? null;
            }

            if (!$projectId) {
                Log::error("FCM Error: Project ID could not be determined from credentials.");
                return false;
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/cloud-platform',
                $credentialsData
            );

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
