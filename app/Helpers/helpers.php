<?php

use App\Enums\Settings as SettingsEnum;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

if (!function_exists("json")) {
    function json(mixed $data = null, ?string $message = null, string $status = 'success', int $headerStatus = 200): JsonResponse
    {
        return response()->json([
            'data' => is_string($data) ? null : $data,
            'message' => is_string($data) ? $data : $message,
            'status' => $status
        ], $headerStatus);
    }
}

if (!function_exists('generateOtp')) {

    /**
     * Generate otp One time Password , It consists of 4 digits
     *
     * @return int
     */
    function generateOtp(): int
    {
        return config('app.debug', false) ? 1111 : rand(1000, 9000);
    }
}

if (!function_exists('setting')) {
    /**
     * Get or set a setting value
     *
     * @param string|SettingsEnum|null $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string|SettingsEnum|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Setting::getAllSettings();
        }

        $keyString = $key instanceof SettingsEnum ? $key->value : $key;

        return Setting::get($keyString, $default);
    }
}

if (!function_exists('settings')) {
    /**
     * Get multiple settings as an array
     *
     * @param array $keys
     * @return array
     */
    function settings(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $keyString = $key instanceof SettingsEnum ? $key->value : $key;
            $result[$keyString] = setting($keyString);
        }

        return $result;
    }
}

if (!function_exists('app_name')) {
    /**
     * Get the application name in the current locale
     *
     * @param string|null $locale
     * @return string
     */
    function app_name(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return match ($locale) {
            'ar' => setting(SettingsEnum::APP_NAME_AR, config('app.name')),
            'en' => setting(SettingsEnum::APP_NAME_EN, config('app.name')),
            default => setting(SettingsEnum::APP_NAME_EN, config('app.name')),
        };
    }
}

if (!function_exists('app_logo')) {
    /**
     * Get the application logo URL
     *
     * @return string|null
     */
    function app_logo(): ?string
    {
        $logo = setting(SettingsEnum::APP_LOGO);
        return $logo ? asset('storage/' . $logo) : null;
    }
}

if (!function_exists('app_icon')) {
    /**
     * Get the application icon URL
     *
     * @return string|null
     */
    function app_icon(): ?string
    {
        $icon = setting(SettingsEnum::APP_ICON);
        return $icon ? asset('storage/' . $icon) : null;
    }
}

if (!function_exists('contact_email')) {
    /**
     * Get contact email
     *
     * @return string|null
     */
    function contact_email(): ?string
    {
        return setting(SettingsEnum::CONTACT_EMAIL);
    }
}

if (!function_exists('contact_phone')) {
    /**
     * Get contact phone
     *
     * @return string|null
     */
    function contact_phone(): ?string
    {
        return setting(SettingsEnum::CONTACT_PHONE);
    }
}

if (!function_exists('contact_whatsapp')) {
    /**
     * Get WhatsApp contact
     *
     * @return string|null
     */
    function contact_whatsapp(): ?string
    {
        return setting(SettingsEnum::CONTACT_WHATSAPP);
    }
}

if (!function_exists('whatsapp_link')) {
    /**
     * Get formatted WhatsApp link
     *
     * @param string|null $message
     * @return string|null
     */
    function whatsapp_link(?string $message = null): ?string
    {
        $number = contact_whatsapp();

        if (!$number) {
            return null;
        }

        // Remove non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);

        $url = "https://wa.me/{$number}";

        if ($message) {
            $url .= '?text=' . urlencode($message);
        }

        return $url;
    }
}

if (!function_exists('social_links')) {
    /**
     * Get all social media links
     *
     * @return array
     */
    function social_links(): array
    {
        return [
            'facebook' => setting(SettingsEnum::FACEBOOK_URL),
            'twitter' => setting(SettingsEnum::TWITTER_URL),
            'instagram' => setting(SettingsEnum::INSTAGRAM_URL),
            'youtube' => setting(SettingsEnum::YOUTUBE_URL),
            'snapchat' => setting(SettingsEnum::SNAPCHAT_URL),
            'tiktok' => setting(SettingsEnum::TIKTOK_URL),
        ];
    }
}

if (!function_exists('is_feature_enabled')) {
    /**
     * Check if a feature is enabled
     *
     * @param SettingsEnum $feature
     * @return bool
     */
    function is_feature_enabled(SettingsEnum $feature): bool
    {
        return (bool) setting($feature, false);
    }
}

if (!function_exists('calculate_tax')) {
    /**
     * Calculate tax for a service type
     *
     * @param float $amount
     * @param string $type 'public', 'private', or 'school'
     * @return float
     */
    function calculate_tax(float $amount, string $type = 'public'): float
    {
        $percentageSetting = match ($type) {
            'private' => SettingsEnum::TAX_PERCENTAGE_PRIVATE,
            'school' => SettingsEnum::TAX_PERCENTAGE_SCHOOL,
            default => SettingsEnum::TAX_PERCENTAGE_PUBLIC,
        };

        $fixedSetting = match ($type) {
            'private' => SettingsEnum::TAX_VALUE_PRIVATE,
            'school' => SettingsEnum::TAX_VALUE_SCHOOL,
            default => SettingsEnum::TAX_VALUE_PUBLIC,
        };

        $percentage = (float) setting($percentageSetting, 0);
        $fixed = (float) setting($fixedSetting, 0);

        $taxFromPercentage = ($amount * $percentage) / 100;

        return $taxFromPercentage + $fixed;
    }
}

if (!function_exists('calculate_app_fee')) {
    /**
     * Calculate app fee for a service type
     *
     * @param float $amount
     * @param string $type 'public', 'private', or 'school'
     * @return float
     */
    function calculate_app_fee(float $amount, string $type = 'public'): float
    {
        $feeSetting = match ($type) {
            'private' => SettingsEnum::APP_FEE_PERCENTAGE_PRIVATE,
            'school' => SettingsEnum::APP_FEE_PERCENTAGE_SCHOOL,
            default => SettingsEnum::APP_FEE_PERCENTAGE_PUBLIC,
        };

        $percentage = (float) setting($feeSetting, 0);

        return ($amount * $percentage) / 100;
    }
}

if (!function_exists('calculate_total_with_tax_and_fee')) {
    /**
     * Calculate total amount with tax and app fee
     *
     * @param float $baseAmount
     * @param string $type 'public', 'private', or 'school'
     * @return array ['base' => float, 'tax' => float, 'fee' => float, 'total' => float]
     */
    function calculate_total_with_tax_and_fee(float $baseAmount, string $type = 'public'): array
    {
        $tax = calculate_tax($baseAmount, $type);
        $fee = calculate_app_fee($baseAmount, $type);
        $total = $baseAmount + $tax + $fee;

        return [
            'base' => round($baseAmount, 2),
            'tax' => round($tax, 2),
            'fee' => round($fee, 2),
            'total' => round($total, 2),
        ];
    }
}

if (!function_exists('enabled_payment_methods')) {
    /**
     * Get list of enabled payment methods
     *
     * @return array
     */
    function enabled_payment_methods(): array
    {
        $methods = [
            'credit_card' => [
                'key' => 'credit_card',
                'label' => 'Credit Card',
                'label_ar' => 'بطاقة ائتمان',
                'enabled' => setting(SettingsEnum::PAYMENT_CREDIT_CARD, true),
            ],
            'mada' => [
                'key' => 'mada',
                'label' => 'MADA',
                'label_ar' => 'مدى',
                'enabled' => setting(SettingsEnum::PAYMENT_MADA, true),
            ],
            'apple_pay' => [
                'key' => 'apple_pay',
                'label' => 'Apple Pay',
                'label_ar' => 'آبل باي',
                'enabled' => setting(SettingsEnum::PAYMENT_APPLE_PAY, true),
            ],
            'stc_pay' => [
                'key' => 'stc_pay',
                'label' => 'STC Pay',
                'label_ar' => 'STC Pay',
                'enabled' => setting(SettingsEnum::PAYMENT_STC_PAY, true),
            ],
            'cash' => [
                'key' => 'cash',
                'label' => 'Cash',
                'label_ar' => 'نقدي',
                'enabled' => setting(SettingsEnum::PAYMENT_CASH, false),
            ],
        ];

        return array_filter($methods, fn($method) => $method['enabled']);
    }
}
