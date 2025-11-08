<?php

namespace App\Services;

use App\Models\Driver;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Log;

class FcmNotificationService
{
    /**
     * Send notification to a specific driver
     *
     * @param Driver $driver
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $image
     * @return void
     */
    public function sendToDriver(Driver $driver, string $title, string $body, array $data = [], string $image = null): void
    {
        try {
            $driver->notify(new GeneralNotification($title, $body, $data, $image));
            
            // Save a simplified record in user's notification table
            $driver->user->notify(new GeneralNotification($title, $body, $data, $image));
            
            Log::info('FCM notification sent to driver', [
                'driver_id' => $driver->id,
                'title' => $title,
                'body' => $body,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification to driver', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to multiple drivers
     *
     * @param array $driverIds
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $image
     * @return void
     */
    public function sendToDrivers(array $driverIds, string $title, string $body, array $data = [], string $image = null): void
    {
        $drivers = Driver::whereIn('id', $driverIds)->get();
        
        foreach ($drivers as $driver) {
            $this->sendToDriver($driver, $title, $body, $data, $image);
        }
    }

    /**
     * Send notification to all active drivers
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $image
     * @return void
     */
    public function sendToAllDrivers(string $title, string $body, array $data = [], string $image = null): void
    {
        $drivers = Driver::where('availability_status', 'available')->get();
        
        foreach ($drivers as $driver) {
            $this->sendToDriver($driver, $title, $body, $data, $image);
        }
    }

    /**
     * Send booking notification to driver
     *
     * @param Driver $driver
     * @param array $bookingData
     * @return void
     */
    public function sendBookingNotification(Driver $driver, array $bookingData): void
    {
        $title = 'New Booking';
        $body = "You have a new booking from {$bookingData['rider_name']}";
        
        $data = [
            'type' => 'new_booking',
            'booking_id' => $bookingData['id'],
            'rider_name' => $bookingData['rider_name'],
            'pickup_location' => $bookingData['pickup_location'],
            'destination' => $bookingData['destination'],
            'scheduled_time' => $bookingData['scheduled_time'],
        ];

        $this->sendToDriver($driver, $title, $body, $data);
    }

    /**
     * Send trip status update notification
     *
     * @param Driver $driver
     * @param string $status
     * @param array $tripData
     * @return void
     */
    public function sendTripStatusUpdate(Driver $driver, string $status, array $tripData): void
    {
        $statusMessages = [
            'started' => 'Trip Started',
            'completed' => 'Trip Completed',
            'cancelled' => 'Trip Cancelled',
        ];

        $title = $statusMessages[$status] ?? 'Trip Status Update';
        $body = "Trip status updated to: {$status}";
        
        $data = [
            'type' => 'trip_status_update',
            'trip_id' => $tripData['id'],
            'status' => $status,
            'trip_data' => $tripData,
        ];

        $this->sendToDriver($driver, $title, $body, $data);
    }

    /**
     * Send wallet update notification
     *
     * @param Driver $driver
     * @param array $walletData
     * @return void
     */
    public function sendWalletUpdate(Driver $driver, array $walletData): void
    {
        $title = 'Wallet Update';
        $body = "Your wallet balance updated. Current balance: {$walletData['balance']}";
        
        $data = [
            'type' => 'wallet_update',
            'balance' => $walletData['balance'],
            'transaction_type' => $walletData['transaction_type'],
            'amount' => $walletData['amount'],
        ];

        $this->sendToDriver($driver, $title, $body, $data);
    }
}
