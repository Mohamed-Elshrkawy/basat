<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Models\TripInstance;
use App\Models\TripStationProgress;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateTripInstances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trips:generate {days=7} {driver_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate trip instances for active schedules for the next X days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->argument('days');
        $driverId = $this->argument('driver_id');

        $this->info("🚌 Generating trip instances for the next {$days} days...");
        $this->newLine();

        $query = Schedule::where('is_active', true)
            ->with('scheduleStops');

        if ($driverId) {
            $this->info("👨‍✈️ Filtering schedules for driver ID: {$driverId}");
            $query->where('driver_id', $driverId);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->error('❌ No active schedules found!');
            return 1;
        }


        $this->info("📋 Found {$schedules->count()} active schedule(s)");
        $this->newLine();

        $tripCount = 0;
        $bar = $this->output->createProgressBar($days);

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->addDays($i);
            $dayOfWeek = $date->format('l');

            foreach ($schedules as $schedule) {

                if (!in_array($dayOfWeek, $schedule->days_of_week ?? [])) {
                    continue;
                }

                $exists = TripInstance::where('schedule_id', $schedule->id)
                    ->where('trip_date', $date->toDateString())
                    ->exists();

                if ($exists) {
                    continue;
                }

                $trip = TripInstance::create([
                    'schedule_id' => $schedule->id,
                    'trip_date' => $date->toDateString(),
                    'status' => 'scheduled',
                ]);

                $allStops = $schedule->scheduleStops()->ordered()->get();

                foreach ($allStops as $index => $scheduleStop) {
                    TripStationProgress::create([
                        'trip_instance_id' => $trip->id,
                        'schedule_stop_id' => $scheduleStop->id,
                        'stop_order' => $index + 1,
                        'direction' => $scheduleStop->direction,
                        'status' => 'pending',
                    ]);
                }

                $tripCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("✅ Generated {$tripCount} trip instance(s) successfully!");
        $this->newLine();

        return 0;
    }
}
