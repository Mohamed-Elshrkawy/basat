<?php
namespace App\Services\PushNotification;

use App\Models\Device;
use Illuminate\Support\Collection;
use RuntimeException;

class FCMService
{
    private const FCM_BASE_URL = 'https://fcm.googleapis.com/v1/projects/';
    private const TOKEN_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const JWT_ALGO = 'RS256';
    private const JWT_TYPE = 'JWT';

    private array $credentials;

    public function __construct()
    {
        $this->credentials = $this->loadCredentials();
    }

    public function sendNotificationsToUsers(array $userIds, array $data): array
    {
        try {
            $deviceTokens = $this->getDeviceTokens($userIds);

            if ($deviceTokens->isEmpty()) {
                return ['success' => false, 'message' => 'No valid device tokens found'];
            }

            $successCount = 0;
            $failures = [];

            foreach ($deviceTokens as $deviceToken) {
                try {
                    $payload = $this->prepareNotificationPayload($deviceToken, $data);
                    $response = $this->sendFCMNotification($payload);

                    if ($this->isSuccessfulResponse($response)) {
                        $successCount++;
                    } else {
                        $failures[] = "Failed to send to token: {$deviceToken} - Response: " . json_encode($response);
                    }
                } catch (\Exception $e) {
                    $failures[] = "Error for token {$deviceToken}: {$e->getMessage()}";
                }
            }

            return [
                'success' => true,
                'message' => "Sent {$successCount} notifications with " . count($failures) . " failures",
                'failures' => $failures,
                'success_count' => $successCount,
                'total_count' => $deviceTokens->count()
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => "Error: {$e->getMessage()}"];
        }
    }

    private function prepareNotificationPayload(string $deviceToken, array $data): array
    {
        $device = Device::where('device_token', $deviceToken)->first();
        $locale = $device?->user?->locale ?? app()->getLocale();
        $type = $device?->type ?? 'android';

        // التعامل مع العناوين متعددة اللغات
        $title = $this->getLocalizedText($data['title'] ?? '', $locale);
        $body = $this->getLocalizedText($data['body'] ?? '', $locale);

        // إعداد البيانات الإضافية
        $fcmData = array_merge([
            'notification_type' => $data['notification_type'] ?? null,
            'notify_id' => $data['notify_id'] ?? null,
        ], $data);

        $payload = [
            'message' => [
                'token' => $deviceToken,
            ]
        ];

        if ($type === 'android') {
            $payload['message']['notification'] = [
                'title' => $title,
                'body' => $body,
            ];
            $payload['message']['data'] = $this->stringifyData($fcmData);
        }

        if ($type === 'ios') {
            $payload['message']['apns'] = [
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default',
                    ],
                ],
                'fcm_options' => [
                    'analytics_label' => 'ios_notification',
                ],
            ];
            $payload['message']['data'] = $this->stringifyData($fcmData);
        }

        return $payload;
    }

    private function sendFCMNotification(array $payload): array
    {
        $accessToken = $this->getAccessToken();
        $url = self::FCM_BASE_URL . $this->credentials['project_id'] . '/messages:send';

        return $this->makeHttpRequest($url, $payload, $accessToken);
    }

    private function getAccessToken(): string
    {
        $jwt = $this->generateJWT();
        $response = $this->makeHttpRequest(
            $this->credentials['token_uri'],
            [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
            null,
            'form'
        );

        if (!isset($response['access_token'])) {
            throw new RuntimeException('Failed to obtain access token: ' . json_encode($response));
        }

        return $response['access_token'];
    }

    private function generateJWT(): string
    {
        $now = time();
        $header = ['alg' => self::JWT_ALGO, 'typ' => self::JWT_TYPE];
        $payload = [
            'iss' => $this->credentials['client_email'],
            'scope' => self::TOKEN_SCOPE,
            'aud' => $this->credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $privateKey = openssl_pkey_get_private($this->credentials['private_key']);
        if (!$privateKey) {
            throw new RuntimeException('Invalid private key');
        }

        $signature = '';
        $signResult = openssl_sign(
            $base64UrlHeader . "." . $base64UrlPayload,
            $signature,
            $privateKey,
            'sha256'
        );

        if (!$signResult) {
            throw new RuntimeException('Failed to sign JWT');
        }

        // تحرير الذاكرة
        openssl_pkey_free($privateKey);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $this->base64UrlEncode($signature);
    }

    private function makeHttpRequest(string $url, array $data, ?string $accessToken = null, string $type = 'json'): array
    {
        $ch = curl_init();
        $headers = ['Content-Type: application/' . ($type === 'json' ? 'json' : 'x-www-form-urlencoded')];

        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $type === 'json' ? json_encode($data) : http_build_query($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Curl error: ' . $error);
        }

        $decodedResponse = json_decode($response, true) ?? [];
        $decodedResponse['status_code'] = $statusCode;

        return $decodedResponse;
    }

    private function loadCredentials(): array
    {
        $credentialsPath = config_path('google-services.json');

        if (!file_exists($credentialsPath)) {
            throw new RuntimeException('Google services credentials file not found');
        }

        $credentialsContent = file_get_contents($credentialsPath);
        if ($credentialsContent === false) {
            throw new RuntimeException('Failed to read credentials file');
        }

        $credentials = json_decode($credentialsContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid credentials file: ' . json_last_error_msg());
        }

        // التحقق من وجود المفاتيح المطلوبة
        $requiredKeys = ['project_id', 'client_email', 'private_key', 'token_uri'];
        foreach ($requiredKeys as $key) {
            if (!isset($credentials[$key])) {
                throw new RuntimeException("Missing required credential: {$key}");
            }
        }

        return $credentials;
    }

    private function getDeviceTokens(array $userIds): Collection
    {
        return Device::whereIn('user_id', $userIds)
            ->whereNotNull('device_token')
            ->where('is_active', true)
            ->where('device_token', '!=', '')
            ->pluck('device_token');
    }

    private function isSuccessfulResponse(array $response): bool
    {
        $statusCode = $response['status_code'] ?? 500;
        return $statusCode >= 200 && $statusCode < 300;
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function getLocalizedText($text, string $locale): string
    {
        if (is_array($text)) {
            return $text[$locale] ?? $text['en'] ?? array_values($text)[0] ?? '';
        }

        return (string) $text;
    }

    private function stringifyData(array $data): array
    {
        $stringifiedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $stringifiedData[$key] = json_encode($value);
            } else {
                $stringifiedData[$key] = (string) $value;
            }
        }
        return $stringifiedData;
    }
}
