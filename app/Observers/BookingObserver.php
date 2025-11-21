<?php

namespace App\Observers;

use App\Models\Booking;
use App\Notifications\GeneralNotification;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        // إشعار للعميل
        $customerData = [
            'title' => [
                'ar' => 'تم إنشاء حجزك بنجاح',
                'en' => 'Your booking has been created successfully'
            ],
            'body' => [
                'ar' => 'رقم الحجز: ' . $booking->booking_number . ' - المبلغ: ' . $booking->total_amount . ' ريال',
                'en' => 'Booking number: ' . $booking->booking_number . ' - Amount: ' . $booking->total_amount . ' SAR'
            ]
        ];
        $booking->user->notify(new GeneralNotification($customerData));

        // إشعار للسائق (إذا كان موجود)
        if ($booking->driver_id) {
            $driverData = [
                'title' => [
                    'ar' => 'حجز جديد',
                    'en' => 'New Booking'
                ],
                'body' => [
                    'ar' => 'لديك حجز جديد رقم ' . $booking->booking_number,
                    'en' => 'You have a new booking #' . $booking->booking_number
                ]
            ];
            $booking->driver?->notify(new GeneralNotification($driverData));
        }
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        // معالجة تغيير الحالة
        if ($booking->isDirty('status')) {
            match($booking->status) {
                'confirmed' => $this->handleBookingConfirmed($booking),
                'in_progress' => $this->handleBookingInProgress($booking),
                'completed' => $this->handleBookingCompleted($booking),
                'cancelled' => $this->handleBookingCancelled($booking),
                default => null,
            };
        }

        // معالجة تغيير حالة الدفع
        if ($booking->isDirty('payment_status') && $booking->payment_status === 'paid') {
            $this->handlePaymentConfirmed($booking);
        }

        // معالجة تغيير حالة الرحلة (للرحلات الخاصة)
        if ($booking->isDirty('trip_status') && $booking->type === 'private_bus') {
            $this->handleTripStatusChanged($booking);
        }
    }

    /**
     * معالجة تأكيد الحجز
     */
    private function handleBookingConfirmed(Booking $booking): void
    {
        // إشعار للعميل
        $customerData = [
            'title' => [
                'ar' => 'تم تأكيد حجزك',
                'en' => 'Your booking has been confirmed'
            ],
            'body' => [
                'ar' => 'تم تأكيد حجزك رقم ' . $booking->booking_number,
                'en' => 'Your booking #' . $booking->booking_number . ' has been confirmed'
            ]
        ];
        $booking->user->notify(new GeneralNotification($customerData));

        // إشعار للسائق
        if ($booking->driver_id) {
            $driverData = [
                'title' => [
                    'ar' => 'تأكيد حجز',
                    'en' => 'Booking Confirmed'
                ],
                'body' => [
                    'ar' => 'تم تأكيد الحجز رقم ' . $booking->booking_number,
                    'en' => 'Booking #' . $booking->booking_number . ' has been confirmed'
                ]
            ];
            $booking->driver?->notify(new GeneralNotification($driverData));
        }
    }

    /**
     * معالجة بدء الرحلة
     */
    private function handleBookingInProgress(Booking $booking): void
    {
        // إشعار للعميل
        $customerData = [
            'title' => [
                'ar' => 'بدأت رحلتك',
                'en' => 'Your trip has started'
            ],
            'body' => [
                'ar' => 'بدأت رحلتك رقم ' . $booking->booking_number,
                'en' => 'Your trip #' . $booking->booking_number . ' has started'
            ]
        ];
        $booking->user->notify(new GeneralNotification($customerData));
    }

    /**
     * معالجة اكتمال الحجز
     */
    private function handleBookingCompleted(Booking $booking): void
    {
        // إشعار للعميل
        $customerData = [
            'title' => [
                'ar' => 'اكتملت رحلتك',
                'en' => 'Your trip has been completed'
            ],
            'body' => [
                'ar' => 'تم إكمال رحلتك رقم ' . $booking->booking_number . ' بنجاح. نتمنى أن تكون قد استمتعت بالرحلة',
                'en' => 'Your trip #' . $booking->booking_number . ' has been completed successfully. We hope you enjoyed your journey'
            ]
        ];
        $booking->user->notify(new GeneralNotification($customerData));

        // إشعار للسائق
        if ($booking->driver_id) {
            $driverData = [
                'title' => [
                    'ar' => 'رحلة مكتملة',
                    'en' => 'Trip Completed'
                ],
                'body' => [
                    'ar' => 'تم إكمال الرحلة رقم ' . $booking->booking_number,
                    'en' => 'Trip #' . $booking->booking_number . ' has been completed'
                ]
            ];
            $booking->driver?->notify(new GeneralNotification($driverData));
        }
    }

    /**
     * معالجة إلغاء الحجز
     */
    private function handleBookingCancelled(Booking $booking): void
    {
        // إشعار للعميل
        $refundMessage = $booking->isPaid()
            ? ' وتم إرجاع المبلغ إلى محفظتك'
            : '';

        $refundMessageEn = $booking->isPaid()
            ? ' and the amount has been refunded to your wallet'
            : '';

        $customerData = [
            'title' => [
                'ar' => 'تم إلغاء حجزك',
                'en' => 'Your booking has been cancelled'
            ],
            'body' => [
                'ar' => 'تم إلغاء حجزك رقم ' . $booking->booking_number . $refundMessage,
                'en' => 'Your booking #' . $booking->booking_number . ' has been cancelled' . $refundMessageEn
            ]
        ];
        $booking->user->notify(new GeneralNotification($customerData));

        // إشعار للسائق
        if ($booking->driver_id) {
            $driverData = [
                'title' => [
                    'ar' => 'تم إلغاء حجز',
                    'en' => 'Booking Cancelled'
                ],
                'body' => [
                    'ar' => 'تم إلغاء الحجز رقم ' . $booking->booking_number,
                    'en' => 'Booking #' . $booking->booking_number . ' has been cancelled'
                ]
            ];
            $booking->driver?->notify(new GeneralNotification($driverData));
        }
    }

    /**
     * معالجة تأكيد الدفع
     */
    private function handlePaymentConfirmed(Booking $booking): void
    {
        // إشعار للعميل
        $customerData = [
            'title' => [
                'ar' => 'تم تأكيد الدفع',
                'en' => 'Payment Confirmed'
            ],
            'body' => [
                'ar' => 'تم تأكيد دفع حجزك رقم ' . $booking->booking_number . ' بمبلغ ' . $booking->total_amount . ' ريال',
                'en' => 'Payment for booking #' . $booking->booking_number . ' of ' . $booking->total_amount . ' SAR has been confirmed'
            ]
        ];
        $booking->user->notify(new GeneralNotification($customerData));

        // إشعار للسائق
        if ($booking->driver_id) {
            $driverData = [
                'title' => [
                    'ar' => 'تم استلام الدفع',
                    'en' => 'Payment Received'
                ],
                'body' => [
                    'ar' => 'تم استلام دفع الحجز رقم ' . $booking->booking_number,
                    'en' => 'Payment received for booking #' . $booking->booking_number
                ]
            ];
            $booking->driver?->notify(new GeneralNotification($driverData));
        }
    }

    /**
     * معالجة تغيير حالة الرحلة (للرحلات الخاصة)
     */
    private function handleTripStatusChanged(Booking $booking): void
    {
        $statusMessages = [
            'pending' => [
                'ar' => 'في انتظار التأكيد',
                'en' => 'Pending confirmation'
            ],
            'on_way_to_pickup' => [
                'ar' => 'السائق في الطريق لاستلامك',
                'en' => 'Driver is on the way to pick you up'
            ],
            'arrived_at_pickup' => [
                'ar' => 'السائق وصل إلى موقع الاستلام',
                'en' => 'Driver has arrived at pickup location'
            ],
            'in_progress' => [
                'ar' => 'الرحلة جارية',
                'en' => 'Trip in progress'
            ],
            'completed' => [
                'ar' => 'تم إكمال الرحلة',
                'en' => 'Trip completed'
            ],
        ];

        $status = $statusMessages[$booking->trip_status] ?? null;

        if ($status) {
            // إشعار للعميل
            $customerData = [
                'title' => [
                    'ar' => 'تحديث حالة الرحلة',
                    'en' => 'Trip Status Update'
                ],
                'body' => [
                    'ar' => 'رحلتك رقم ' . $booking->booking_number . ': ' . $status['ar'],
                    'en' => 'Your trip #' . $booking->booking_number . ': ' . $status['en']
                ]
            ];
            $booking->user->notify(new GeneralNotification($customerData));
        }
    }
}
