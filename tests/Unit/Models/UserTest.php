<?php

namespace Tests\Unit\Models;

use App\Models\Child;
use App\Models\Device;
use App\Models\Driver;
use App\Models\ProblemReport;
use App\Models\Rating;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);
    }

    public function test_user_has_devices_relationship(): void
    {
        $device = Device::create([
            'user_id' => $this->user->id,
            'device_token' => 'test-token',
            'type' => 'android',
            'status' => true,
        ]);

        $this->assertTrue($this->user->devices->contains($device));
        $this->assertCount(1, $this->user->devices);
    }

    public function test_user_has_driver_relationship(): void
    {
        $driver = Driver::create([
            'user_id' => $this->user->id,
            'license_number' => 'DL123456',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Driver::class, $this->user->driver);
        $this->assertEquals($driver->id, $this->user->driver->id);
    }

    public function test_user_has_children_relationship(): void
    {
        $child = Child::create([
            'parent_id' => $this->user->id,
            'name' => 'Jane Doe',
            'age' => 10,
        ]);

        $this->assertTrue($this->user->children->contains($child));
        $this->assertCount(1, $this->user->children);
    }

    public function test_user_has_trips_as_rider_relationship(): void
    {
        $trip = Trip::create([
            'rider_id' => $this->user->id,
            'pickup_address' => 'Address A',
            'dropoff_address' => 'Address B',
            'pickup_lat' => 30.0444,
            'pickup_lng' => 31.2357,
            'dropoff_lat' => 30.0500,
            'dropoff_lng' => 31.2400,
            'status' => 'pending',
        ]);

        $this->assertTrue($this->user->tripsAsRider->contains($trip));
        $this->assertCount(1, $this->user->tripsAsRider);
    }

    public function test_user_has_trips_as_driver_relationship(): void
    {
        $trip = Trip::create([
            'driver_id' => $this->user->id,
            'pickup_address' => 'Address A',
            'dropoff_address' => 'Address B',
            'pickup_lat' => 30.0444,
            'pickup_lng' => 31.2357,
            'dropoff_lat' => 30.0500,
            'dropoff_lng' => 31.2400,
            'status' => 'pending',
        ]);

        $this->assertTrue($this->user->tripsAsDriver->contains($trip));
        $this->assertCount(1, $this->user->tripsAsDriver);
    }

    public function test_user_has_ratings_given_relationship(): void
    {
        $ratedUser = User::factory()->create();

        $rating = Rating::create([
            'rater_id' => $this->user->id,
            'rated_id' => $ratedUser->id,
            'rating' => 5,
            'comment' => 'Great service',
        ]);

        $this->assertTrue($this->user->ratingsGiven->contains($rating));
        $this->assertCount(1, $this->user->ratingsGiven);
    }

    public function test_user_has_ratings_received_relationship(): void
    {
        $raterUser = User::factory()->create();

        $rating = Rating::create([
            'rater_id' => $raterUser->id,
            'rated_id' => $this->user->id,
            'rating' => 4,
            'comment' => 'Good driver',
        ]);

        $this->assertTrue($this->user->ratingsReceived->contains($rating));
        $this->assertCount(1, $this->user->ratingsReceived);
    }

    public function test_user_has_problem_reports_relationship(): void
    {
        $trip = Trip::create([
            'rider_id' => $this->user->id,
            'pickup_address' => 'Address A',
            'dropoff_address' => 'Address B',
            'pickup_lat' => 30.0444,
            'pickup_lng' => 31.2357,
            'dropoff_lat' => 30.0500,
            'dropoff_lng' => 31.2400,
            'status' => 'pending',
        ]);

        $report = ProblemReport::create([
            'reporter_id' => $this->user->id,
            'trip_id' => $trip->id,
            'description' => 'Driver was late',
            'status' => 'pending',
        ]);

        $this->assertTrue($this->user->problemReports->contains($report));
        $this->assertCount(1, $this->user->problemReports);
    }

    public function test_password_is_hidden(): void
    {
        $this->user->password = 'secret-password';
        $array = $this->user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_reset_code_is_hidden(): void
    {
        $this->user->reset_code = '123456';
        $array = $this->user->toArray();

        $this->assertArrayNotHasKey('reset_code', $array);
    }

    public function test_get_default_avatar_url_for_english_name(): void
    {
        $this->user->name = 'John Doe';
        $avatarUrl = $this->user->getDefaultAvatarUrl();

        $this->assertStringContainsString('ui-avatars.com', $avatarUrl);
        $this->assertStringContainsString('J%2BD', $avatarUrl); // J+D initials
    }

    public function test_get_default_avatar_url_for_single_word_name(): void
    {
        $this->user->name = 'John';
        $avatarUrl = $this->user->getDefaultAvatarUrl();

        $this->assertStringContainsString('ui-avatars.com', $avatarUrl);
        $this->assertStringContainsString('Jo', $avatarUrl); // First 2 characters
    }

    public function test_get_default_avatar_url_for_arabic_name(): void
    {
        $this->user->name = 'أحمد محمد';
        $avatarUrl = $this->user->getDefaultAvatarUrl();

        $this->assertStringContainsString('ui-avatars.com', $avatarUrl);
    }

    public function test_is_arabic_returns_true_for_arabic_text(): void
    {
        $this->assertTrue($this->user->isArabic('أحمد'));
        $this->assertTrue($this->user->isArabic('محمد علي'));
    }

    public function test_is_arabic_returns_false_for_english_text(): void
    {
        $this->assertFalse($this->user->isArabic('John'));
        $this->assertFalse($this->user->isArabic('John Doe'));
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $this->user->email_verified_at = '2024-01-01 00:00:00';
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $this->user->email_verified_at);
    }

    public function test_mobile_verified_at_is_cast_to_datetime(): void
    {
        $this->user->mobile_verified_at = '2024-01-01 00:00:00';
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $this->user->mobile_verified_at);
    }
}
