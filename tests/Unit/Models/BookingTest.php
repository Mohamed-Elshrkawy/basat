<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->schedule = Schedule::create([
            'route_id' => 1,
            'driver_id' => User::factory()->create()->id,
            'vehicle_id' => 1,
            'departure_time' => '08:00:00',
            'arrival_time' => '10:00:00',
            'available_seats' => 40,
            'price' => 50.00,
            'status' => 'active',
        ]);
    }

    public function test_booking_generates_unique_booking_number_on_creation(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 2,
            'total_amount' => 100.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertNotNull($booking->booking_number);
        $this->assertStringStartsWith('BK', $booking->booking_number);
    }

    public function test_booking_number_is_unique(): void
    {
        $booking1 = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $booking2 = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertNotEquals($booking1->booking_number, $booking2->booking_number);
    }

    public function test_booking_has_user_relationship(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(User::class, $booking->user);
        $this->assertEquals($this->user->id, $booking->user->id);
    }

    public function test_booking_has_schedule_relationship(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Schedule::class, $booking->schedule);
        $this->assertEquals($this->schedule->id, $booking->schedule->id);
    }

    public function test_pending_scope_returns_only_pending_bookings(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $pendingBookings = Booking::pending()->get();

        $this->assertCount(1, $pendingBookings);
        $this->assertEquals('pending', $pendingBookings->first()->status);
    }

    public function test_confirmed_scope_returns_only_confirmed_bookings(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $confirmedBookings = Booking::confirmed()->get();

        $this->assertCount(1, $confirmedBookings);
        $this->assertEquals('confirmed', $confirmedBookings->first()->status);
    }

    public function test_is_paid_returns_true_when_payment_status_is_paid(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $this->assertTrue($booking->isPaid());
    }

    public function test_is_paid_returns_false_when_payment_status_is_not_paid(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertFalse($booking->isPaid());
    }

    public function test_is_pending_returns_true_when_status_is_pending(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertTrue($booking->isPending());
    }

    public function test_is_confirmed_returns_true_when_status_is_confirmed(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $this->assertTrue($booking->isConfirmed());
    }

    public function test_is_cancelled_returns_true_when_status_is_cancelled(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'cancelled',
        ]);

        $this->assertTrue($booking->isCancelled());
    }

    public function test_can_be_cancelled_returns_true_for_pending_booking(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertTrue($booking->canBeCancelled());
    }

    public function test_can_be_cancelled_returns_true_for_confirmed_booking(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $this->assertTrue($booking->canBeCancelled());
    }

    public function test_can_be_cancelled_returns_false_for_completed_booking(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        $this->assertFalse($booking->canBeCancelled());
    }

    public function test_mark_as_paid_updates_booking_status(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 1,
            'total_amount' => 50.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $booking->markAsPaid('TXN123456');
        $booking->refresh();

        $this->assertEquals('paid', $booking->payment_status);
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('TXN123456', $booking->transaction_id);
        $this->assertNotNull($booking->paid_at);
    }

    public function test_cancel_updates_booking_status_and_returns_seats(): void
    {
        $initialSeats = $this->schedule->available_seats;

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 2,
            'total_amount' => 100.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $booking->cancel('Changed plans');
        $booking->refresh();
        $this->schedule->refresh();

        $this->assertEquals('cancelled', $booking->status);
        $this->assertEquals('Changed plans', $booking->cancellation_reason);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertEquals($initialSeats + 2, $this->schedule->available_seats);
    }

    public function test_seat_numbers_is_cast_to_array(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->schedule->id,
            'travel_date' => now()->addDay(),
            'trip_type' => 'one_way',
            'number_of_seats' => 2,
            'seat_numbers' => [1, 2],
            'total_amount' => 100.00,
            'payment_method' => 'wallet',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->assertIsArray($booking->seat_numbers);
        $this->assertEquals([1, 2], $booking->seat_numbers);
    }
}
