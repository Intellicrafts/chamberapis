<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LawyersCase;

class LawyersCaseSeeder extends Seeder
{
    public function run()
    {
        LawyersCase::factory()->count(20)->create();
    }
}
