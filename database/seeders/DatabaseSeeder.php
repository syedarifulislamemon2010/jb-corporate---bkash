<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'G S Kibria',
            'email' => 'kibria@jb.com',
            'mobile_no' => '01738535099',
            'organization' => 'Janata Bank PLC',
        ]);
    }
}
