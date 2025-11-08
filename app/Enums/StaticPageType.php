<?php
// app/Enums/StaticPageType.php

namespace App\Enums;

use App\Traits\EnumTo;

enum StaticPageType: string
{
    use EnumTo;

    case TERMS = 'terms';
    case PRIVACY = 'privacy-policy';
    case ABOUT = 'about-us';
    case CANCELLATION = 'cancellation-policy';

    public function getTitle(): array
    {
        return match($this) {
            self::TERMS => [
                'en' => 'Terms & Conditions',
                'ar' => 'الشروط والأحكام',
            ],
            self::PRIVACY => [
                'en' => 'Privacy Policy',
                'ar' => 'سياسة الخصوصية',
            ],
            self::ABOUT => [
                'en' => 'About Us',
                'ar' => 'من نحن',
            ],
            self::CANCELLATION => [
                'en' => 'Cancellation Policy',
                'ar' => 'سياسة الإلغاء',
            ],
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::TERMS => 'Terms & Conditions',
            self::PRIVACY => 'Privacy Policy',
            self::ABOUT => 'About Us',
            self::CANCELLATION => 'Cancellation Policy',
        };
    }

    public function getKey(): string
    {
        return $this->value;
    }

    public static function getAll(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
