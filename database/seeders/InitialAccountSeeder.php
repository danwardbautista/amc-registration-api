<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class InitialAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $initialEmail = env('INITIAL_EMAIL');
        $initialPassword = env('INITIAL_PASSWORD');

        //Initial validations
        if (!$initialEmail || !$initialPassword) {
            throw new Exception('INITIAL_EMAIL and INITIAL_PASSWORD environment variables must be set.');
        }

        if (!filter_var($initialEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('INITIAL_EMAIL must be a valid email address.');
        }

        if (strlen($initialPassword) < 8) {
            throw new Exception('INITIAL_PASSWORD must be at least 8 characters long.');
        }

        // Check if exist
        if (DB::table('users')->where('email', $initialEmail)->exists()) {
            $this->command->info('Owner account already exists. Skipping creation.');
            return;
        }

        DB::table('users')->insert([
            'name'       => 'Owner',
            'email'      => $initialEmail,
            'password'   => Hash::make($initialPassword),
            'role'       => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
