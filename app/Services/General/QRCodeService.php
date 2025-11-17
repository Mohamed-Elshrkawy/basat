<?php

namespace App\Services\General;

use App\Models\Booking;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    /**
     * Generate QR Code for booking
     *
     * @param Booking $booking
     * @return string QR Code URL
     */
    public function generateForBooking(Booking $booking): string
    {
        // البيانات اللي هتكون في الـ QR Code
        $qrData = [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'schedule_id' => $booking->schedule_id,
            'travel_date' => $booking->travel_date->format('Y-m-d'),
            'number_of_seats' => $booking->number_of_seats,
            'seat_numbers' => $booking->seat_numbers,
            'status' => $booking->status,
            'verification_token' => $this->generateVerificationToken($booking),
        ];

        // تحويل البيانات لـ JSON
        $qrContent = json_encode($qrData);

        try {
            // محاولة استخدام Simple QR Code مع GD
            $qrCode = QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($qrContent);

            // حفظ الـ QR Code
            $filename = "qrcodes/booking_{$booking->booking_number}.png";
            Storage::disk('public')->put($filename, $qrCode);

            return Storage::disk('public')->url($filename);

        } catch (\Exception $e) {
            // إذا فشل، استخدم API خارجي
            return $this->generateUsingAPI($qrContent, $booking->booking_number);
        }
    }

    /**
     * Generate QR Code using external API (fallback)
     *
     * @param string $content
     * @param string $bookingNumber
     * @return string
     */
    private function generateUsingAPI(string $content, string $bookingNumber): string
    {
        try {
            // استخدام QR Code API مجاني
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/";
            $params = [
                'size' => '300x300',
                'data' => $content,
                'format' => 'png',
            ];

            $qrUrl = $apiUrl . '?' . http_build_query($params);

            // تحميل الصورة مع timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5, // 5 seconds timeout
                    'ignore_errors' => true,
                ],
            ]);
            $imageContent = @file_get_contents($qrUrl, false, $context);

            if ($imageContent) {
                // حفظ الصورة
                $filename = "qrcodes/booking_{$bookingNumber}.png";
                Storage::disk('public')->put($filename, $imageContent);

                return Storage::disk('public')->url($filename);
            }

            // إذا فشل، ارجع الـ API URL مباشرة
            return $qrUrl;

        } catch (\Exception $e) {
            // آخر حل: ارجع URL للـ API مباشرة
            return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($content);
        }
    }

    /**
     * Generate verification token
     *
     * @param Booking $booking
     * @return string
     */
    private function generateVerificationToken(Booking $booking): string
    {
        return hash('sha256', $booking->id . $booking->booking_number . config('app.key'));
    }

    /**
     * Verify QR Code data
     *
     * @param array $qrData
     * @return bool
     */
    public function verifyQRCode(array $qrData): bool
    {
        if (!isset($qrData['booking_id']) || !isset($qrData['verification_token'])) {
            return false;
        }

        $booking = Booking::find($qrData['booking_id']);

        if (!$booking) {
            return false;
        }

        $expectedToken = $this->generateVerificationToken($booking);

        return hash_equals($expectedToken, $qrData['verification_token']);
    }

    /**
     * Delete QR Code file
     *
     * @param Booking $booking
     * @return bool
     */
    public function deleteQRCode(Booking $booking): bool
    {
        $filename = "qrcodes/booking_{$booking->booking_number}.png";

        if (Storage::disk('public')->exists($filename)) {
            return Storage::disk('public')->delete($filename);
        }

        return false;
    }
}
