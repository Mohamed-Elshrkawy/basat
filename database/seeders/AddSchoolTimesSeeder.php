<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;

class AddSchoolTimesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add default school times
        $schools = School::all();
        
        foreach ($schools as $school) {
            // Default times: 7:30 AM for departure, 2:00 PM for return
            $school->update([
                'departure_time' => '07:30:00',
                'return_time' => '14:00:00',
            ]);
        }
        
        $this->command->info('School times added successfully');
    }
}
