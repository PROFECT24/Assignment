<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BulkUserSeeder extends Seeder
{
    private $chunkSize = 500; // Process 500 users at a time to avoid memory issues
    private $totalUsers = 10000;

    public function run(): void
    {
        $this->command->info('🚀 Starting bulk user seeding for 10,000+ users...');
        $startTime = microtime(true);

        // Create main users first
        $this->createMainUsers();

        // Create bulk users in optimized batches
        $this->createBulkUsers();

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->command->info("\n✅ Successfully created " . User::count() . " users in {$executionTime} seconds");
        $this->command->info('📊 User distribution:');
        $this->command->info('   - Admin: 1 user (admin@example.com)');
        $this->command->info('   - Manager: 1 user (manager@example.com)');
        $this->command->info('   - Regular: 1 user (user@example.com)');
        $this->command->info('   - Test Users: ' . ($this->totalUsers - 3) . ' generated users');
    }

    private function createMainUsers(): void
    {
        $this->command->info('Creating main users...');

        $mainUsers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@example.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($mainUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }

    private function createBulkUsers(): void
    {
        $usersToCreate = $this->totalUsers - 3; // Subtract the 3 main users
        $chunks = ceil($usersToCreate / $this->chunkSize);

        $this->command->info("Creating {$usersToCreate} bulk users in {$chunks} chunks of {$this->chunkSize}...");

        $progressBar = $this->command->getOutput()->createProgressBar($chunks);
        
        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $this->createUserChunk($this->chunkSize);
            $progressBar->advance();
            
            // Optional: Show memory usage
            if ($chunk % 5 === 0) {
                $memory = round(memory_get_usage(true) / 1024 / 1024, 2);
                $this->command->info(" [Memory: {$memory}MB]");
            }
        }

        $progressBar->finish();
    }

    private function createUserChunk($chunkSize): void
    {
        $users = [];
        
        for ($i = 0; $i < $chunkSize; $i++) {
            $users[] = [
                'name' => $this->generateUniqueName(),
                'email' => $this->generateUniqueEmail(),
                'password' => Hash::make('password'),
                'role' => $this->getRandomRole(),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Use insert for better performance (bypasses model events)
        User::insert($users);
    }

    private function generateUniqueName(): string
    {
        $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Lisa', 'Robert', 'Jennifer', 'William', 'Maria'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function generateUniqueEmail(): string
    {
        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'example.com'];
        $name = strtolower(str_replace(' ', '.', $this->generateUniqueName()));
        $randomNumber = mt_rand(1000, 999999);
        
        return $name . $randomNumber . '@' . $domains[array_rand($domains)];
    }

    private function getRandomRole(): string
    {
        $roles = ['user', 'user', 'user', 'user', 'manager']; // 80% users, 20% managers
        return $roles[array_rand($roles)];
    }
}