<?php
 
namespace Database\Seeders;
 
use Illuminate\Database\Seeder;
use App\Models\Schedule;
 
class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tomorrow = now()->addDay()->toDateString();
 
        Schedule::create([
            'field_id' => 1,
            'date' => $tomorrow,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'price' => 40000,
        ]);
 
        Schedule::create([
            'field_id' => 1,
            'date' => $tomorrow,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'price' => 40000,
        ]);
 
        Schedule::create([
            'field_id' => 2,
            'date' => $tomorrow,
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'price' => 50000,
        ]);
    }
}
