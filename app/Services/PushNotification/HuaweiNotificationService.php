<?php

namespace App\Services\PushNotification;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuaweiNotificationService
{
    private string $appId = '113182115';
    private string $clientId = '1589290569492879040';
    private string $clientSecret = '607D8AA15FEB9F1BE5AC29DA31AAE04726A8E139576D353A97DBDAB1428E72DB';
    private string $token = '';

    /**
     * @throws ConnectionException
     */
    public function __construct(private readonly array|string $deviceTokens = [])
    {
        $this->token = $this->getAccessToken();
    }

    /**
     * @throws ConnectionException
     */
    public static function build(array|string $deviceTokens = []): self
    {
        return new self($deviceTokens);
    }

    /**
     * @param $title
     * @param $body
     * @param array $metaData
     * @return bool
     */
    public function send($title, $body, array $metaData = []): bool
    {
        if (count($this->deviceTokens) < 1) {
            return false;
        }

        try {

            $payload = [
                'validate_only' => false,
                'message' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => json_encode($metaData),
                    'token' => (array)$this->deviceTokens,
                ],
            ];

            $response = Http::withToken($this->token)
                ->post("https://push-api.cloud.huawei.com/v1/{$this->appId}/messages:send", $payload);

//            dd($response->json());
            return $response->getStatusCode() === 200;
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
            return false;
        }
    }

    /**
     * @throws ConnectionException
     */
    public function getAccessToken()
    {
        $response = Http::asForm()->post('https://oauth-login.cloud.huawei.com/oauth2/v3/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }
        Log::error('Failed to get Huawei access token: ' . $response->body());
        return '';
    }


}
