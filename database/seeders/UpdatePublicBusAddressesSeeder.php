<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\Booking;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdatePublicBusAddressesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update the public bus trips with the addresses
        $publicBusTrips = Trip::where('type', 'public_bus')
            ->where(function($query) {
                $query->whereNull('pickup_address')
                      ->orWhereNull('dropoff_address');
            })
            ->get();

        foreach ($publicBusTrips as $trip) {
            // Get the addresses from the first booking
            $firstBooking = $trip->bookings()->with(['pickupStop', 'dropoffStop'])->first();
            
            if ($firstBooking) {
                $pickupAddress = $firstBooking->pickupStop ? $firstBooking->pickupStop->name : null;
                $dropoffAddress = $firstBooking->dropoffStop ? $firstBooking->dropoffStop->name : null;
                
                $trip->update([
                    'pickup_address' => $pickupAddress,
                    'dropoff_address' => $dropoffAddress,
                ]);
            } else {
                // If there is no booking, use dummy addresses
                $trip->update([
                    'pickup_address' => 'Pickup Station',
                    'dropoff_address' => 'Dropoff Station',
                ]);
            }
        }

        $this->command->info("Updated {$publicBusTrips->count()} public bus trips with addresses.");
    }
}
