<?php

namespace App\Enums;

enum Settings: string
{
    #--------------------------------------------
    # Contact Information
    #--------------------------------------------
    case CONTACT_EMAIL = 'contact_email'; // __("Contact Email")
    case CONTACT_PHONE = 'contact_phone'; // __("Contact Phone")
    case CONTACT_WHATSAPP = 'contact_whatsapp'; // __("Contact WhatsApp")
    case CONTACT_ADDRESS = 'contact_address'; // __("Contact Address")
    case FACEBOOK_URL = 'facebook_url'; // __("Facebook URL")
    case TWITTER_URL = 'twitter_url'; // __("Twitter URL")
    case INSTAGRAM_URL = 'instagram_url'; // __("Instagram URL")
    case YOUTUBE_URL = 'youtube_url'; // __("YouTube URL")
    case SNAPCHAT_URL = 'snapchat_url'; // __("Snapchat URL")
    case TIKTOK_URL = 'tiktok_url'; // __("TikTok URL")

    #--------------------------------------------
    # Platform Settings
    #--------------------------------------------
    case ENABLE_SEAT_BOOKING = 'enable_seat_booking'; // __("Enable Seat Booking")
    case ENABLE_PRIVATE_BUS = 'enable_private_bus'; // __("Enable Private Bus")
    case ENABLE_SUBSCRIPTIONS = 'enable_subscriptions'; // __("Enable Subscriptions")

    case TAX_PERCENTAGE_PUBLIC = 'tax_percentage_public'; // __("Public Trip Tax Percentage")
    case TAX_PERCENTAGE_PRIVATE = 'tax_percentage_private'; // __("Private Trip Tax Percentage")
    case TAX_PERCENTAGE_SCHOOL = 'tax_percentage_school'; // __("School Trip Tax Percentage")

    case TAX_VALUE_PUBLIC = 'tax_value_public'; // __("Public Trip Tax Value")
    case TAX_VALUE_PRIVATE = 'tax_value_private'; // __("Private Trip Tax Value")
    case TAX_VALUE_SCHOOL = 'tax_value_school'; // __("School Trip Tax Value")

    case APP_FEE_PERCENTAGE_PUBLIC = 'app_fee_percentage_public'; // __("App Fee Percentage (Public)")
    case APP_FEE_PERCENTAGE_PRIVATE = 'app_fee_percentage_private'; // __("App Fee Percentage (Private)")
    case APP_FEE_PERCENTAGE_SCHOOL = 'app_fee_percentage_school'; // __("App Fee Percentage (School)")

    #--------------------------------------------
    # App Info
    #--------------------------------------------
    case APP_NAME_AR = 'app_name_ar'; // __("App Name (Arabic)")
    case APP_NAME_EN = 'app_name_en'; // __("App Name (English)")
    case APP_VERSION = 'app_version'; // __("App Version")
    case APP_LOGO = 'app_logo'; // __("App Logo")
    case APP_ICON = 'app_icon'; // __("App Icon")

    #--------------------------------------------
    # Payment Methods
    #--------------------------------------------
    case PAYMENT_CREDIT_CARD = 'payment_credit_card'; // __("Enable Credit Card")
    case PAYMENT_MADA = 'payment_mada'; // __("Enable MADA")
    case PAYMENT_APPLE_PAY = 'payment_apple_pay'; // __("Enable Apple Pay")
    case PAYMENT_STC_PAY = 'payment_stc_pay'; // __("Enable STC Pay")
    case PAYMENT_CASH = 'payment_cash'; // __("Enable Cash Payment")
    case PAYMENT_WALLET = 'payment_wallet'; // __("Enable Wallet Payment")

    #--------------------------------------------
    # BASE HELPERS
    #--------------------------------------------
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function metadata(): array
    {
        return match ($this) {

            #--------------------------------------------
            # CONTACT INFORMATION
            #--------------------------------------------
            self::CONTACT_EMAIL => [
                'type' => 'email',
                'group' => 'contact',
                'label' => __('Contact Email'),
                'rules' => ['required', 'email'],
                'span' => 1,
            ],
            self::CONTACT_PHONE, self::CONTACT_WHATSAPP => [
                'type' => 'text',
                'group' => 'contact',
                'label' =>  str($this->value)->replace('_', ' ')->title(),
                'rules' => ['nullable', 'string', 'max:20'],
                'span' => 1,
            ],
            self::CONTACT_ADDRESS => [
                'type' => 'text',
                'group' => 'contact',
                'label' => __('Contact Address'),
                'rules' => ['nullable', 'string', 'max:255'],
                'span' => 1,
            ],
            self::FACEBOOK_URL, self::TWITTER_URL, self::INSTAGRAM_URL,
            self::YOUTUBE_URL, self::SNAPCHAT_URL, self::TIKTOK_URL => [
                'type' => 'url',
                'group' => 'contact',
                'label' =>  str($this->value)->replace('_', ' ')->title(),
                'rules' => ['nullable', 'url'],
                'span' => 1,
            ],

            #--------------------------------------------
            # APP INFO
            #--------------------------------------------
            self::APP_NAME_AR, self::APP_NAME_EN => [
                'type' => 'text',
                'group' => 'app_info',
                'label' =>  str($this->value)->replace('_', ' ')->title(),
                'rules' => ['nullable', 'string', 'max:255'],
                'span' => 2,
            ],
            self::APP_VERSION => [
                'type' => 'text',
                'group' => 'app_info',
                'label' => __('App Version'),
                'rules' => ['nullable', 'string', 'max:10'],
                'span' => 2,
            ],
            self::APP_LOGO, self::APP_ICON => [
                'type' => 'image',
                'group' => 'app_info',
                'label' =>  str($this->value)->replace('_', ' ')->title(),
                'rules' => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico', 'max:2048'],
                'span' => 1,
            ],

            #--------------------------------------------
            # PLATFORM SETTINGS
            #--------------------------------------------
            self::ENABLE_SEAT_BOOKING, self::ENABLE_PRIVATE_BUS, self::ENABLE_SUBSCRIPTIONS => [
                'type' => 'boolean',
                'group' => 'platform',
                'label' =>  str($this->value)->replace('_', ' ')->title(),
                'rules' => ['boolean'],
                'span' => 2,
            ],
            self::TAX_PERCENTAGE_PUBLIC, self::TAX_PERCENTAGE_PRIVATE, self::TAX_PERCENTAGE_SCHOOL,
            self::TAX_VALUE_PUBLIC, self::TAX_VALUE_PRIVATE, self::TAX_VALUE_SCHOOL,
            self::APP_FEE_PERCENTAGE_PUBLIC, self::APP_FEE_PERCENTAGE_PRIVATE, self::APP_FEE_PERCENTAGE_SCHOOL => [
                'type' => 'number',
                'group' => 'platform',
                'label' =>  str($this->value)->replace('_', ' ')->title(),
                'rules' => ['nullable', 'numeric', 'min:0'],
                'span' => 1,
            ],

            #--------------------------------------------
            # PAYMENT METHODS
            #--------------------------------------------
            self::PAYMENT_CREDIT_CARD, self::PAYMENT_MADA, self::PAYMENT_APPLE_PAY,
            self::PAYMENT_STC_PAY, self::PAYMENT_CASH, self::PAYMENT_WALLET => [
                'type' => 'boolean',
                'group' => 'payment_methods',
                'label' =>  str($this->value)->replace('_', ' ')->title(),
                'rules' => ['boolean'],
                'span' => 2,
            ],
        };
    }

    #--------------------------------------------
    # GROUPING & HELPERS
    #--------------------------------------------
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $case) {
            $meta = $case->metadata();
            $group = $meta['group'];

            $groups[$group]['label'] ??=  __(ucfirst(str_replace('_', ' ', $group)));
            $groups[$group]['icon'] ??= match ($group) {
                'contact' => 'heroicon-o-phone',
                'platform' => 'heroicon-o-cog-6-tooth',
                'app_info' => 'heroicon-o-cog',
                'payment_methods' => 'heroicon-o-credit-card',
                default => 'heroicon-o-adjustments-horizontal',
            };

            $groups[$group]['settings'][$case->value] = $meta;
        }

        return $groups;
    }

    public function getLabel(): string
    {
        return $this->metadata()['label'];
    }

    public function getType(): string
    {
        return $this->metadata()['type'];
    }

    public function getRules(): array
    {
        return $this->metadata()['rules'];
    }

    public function getSpan(): string|int
    {
        return $this->metadata()['span'] ?? 1;
    }
}
