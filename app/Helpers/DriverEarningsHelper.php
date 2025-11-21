<?php

namespace App\Helpers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DriverEarningsHelper
{
    /**
     * Process driver earnings for a booking when completed
     *
     * @param Booking $booking
     * @return array
     * @throws \Exception
     */
    public static function processDriverEarnings(Booking $booking): array
    {
        if (!$booking->driver_id) {
            throw new \Exception('Booking has no driver assigned');
        }

        $driver = User::find($booking->driver_id);

        if (!$driver) {
            throw new \Exception('Driver not found');
        }

        // تحديد نسبة عمولة التطبيق حسب نوع الحجز
        $taxPercentage = match($booking->type) {
            'public_bus' => (float) setting('tax_percentage_public', 5),
            'private_bus' => (float) setting('tax_percentage_private', 7),
            default => 0,
        };

        $bookingAmount = (float) $booking->total_amount;
        $appFees = ($bookingAmount * $taxPercentage) / 100;
        $driverEarnings = $bookingAmount - $appFees;

        // حفظ القيم في جدول الحجوزات
        $booking->update([
            'app_fees' => $appFees,
            'driver_earnings' => $driverEarnings,
        ]);

        // منطق الدفع حسب طريقة الدفع
        if ($booking->payment_method === 'cash') {
            // نقدي: السائق جمع المبلغ، نخصم منه مستحقات التطبيق فقط
            self::deductAppFees($driver, $booking, $appFees);

            return [
                'payment_method' => 'cash',
                'booking_amount' => $bookingAmount,
                'app_fees' => $appFees,
                'driver_earnings' => $driverEarnings,
                'action' => 'deducted_fees',
                'message' => 'تم خصم مستحقات التطبيق من محفظة السائق'
            ];
        } else {
            // أي طريقة دفع أخرى (محفظة، أونلاين، إلخ): نضيف للسائق ثم نخصم المستحقات
            self::creditDriverEarnings($driver, $booking, $driverEarnings);
            self::deductAppFees($driver, $booking, $appFees);

            return [
                'payment_method' => $booking->payment_method,
                'booking_amount' => $bookingAmount,
                'app_fees' => $appFees,
                'driver_earnings' => $driverEarnings,
                'action' => 'credited_and_deducted',
                'message' => 'تم إضافة المبلغ للسائق وخصم مستحقات التطبيق'
            ];
        }
    }

    /**
     * Credit driver with earnings
     */
    private static function creditDriverEarnings(User $driver, Booking $booking, float $amount): void
    {
        $desc = [
            'ar' => 'أرباح من حجز رقم ' . $booking->booking_number . ' - ' . $amount . ' ريال',
            'en' => 'Earnings from booking #' . $booking->booking_number . ' - ' . $amount . ' SAR',
        ];

        $driver->deposit($amount, $desc);
    }

    /**
     * Deduct app fees from driver wallet
     */
    private static function deductAppFees(User $driver, Booking $booking, float $fees): void
    {
        $desc = [
            'ar' => 'مستحقات التطبيق من حجز رقم ' . $booking->booking_number . ' - ' . $fees . ' ريال',
            'en' => 'App fees for booking #' . $booking->booking_number . ' - ' . $fees . ' SAR',
        ];

        // التحقق من رصيد السائق
        if ($driver->balance < $fees) {
            throw new \Exception('Driver has insufficient balance to pay app fees');
        }

        $driver->withdraw($fees, $desc);
    }

    /**
     * Calculate earnings without processing (for preview)
     */
    public static function calculateEarnings(Booking $booking): array
    {
        $taxPercentage = match($booking->type) {
            'public_bus' => (float) setting('tax_percentage_public', 5),
            'private_bus' => (float) setting('tax_percentage_private', 7),
            default => 0,
        };

        $bookingAmount = (float) $booking->total_amount;
        $appFees = ($bookingAmount * $taxPercentage) / 100;
        $driverEarnings = $bookingAmount - $appFees;

        return [
            'booking_amount' => $bookingAmount,
            'tax_percentage' => $taxPercentage,
            'app_fees' => $appFees,
            'driver_earnings' => $driverEarnings,
        ];
    }
}
