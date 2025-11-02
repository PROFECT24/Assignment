<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FactoryUserSeeder extends Seeder
{
    private $totalUsers = 10000;
    private $chunkSize = 1000;

    public function run(): void
    {
        $this->command->info("🎯 Creating {$this->totalUsers} users using Factory in chunks...");
        $startTime = microtime(true);

        // Create main users
        $this->createMainUsers();

        // Calculate how many more users we need
        $existingUsers = User::count();
        $usersToCreate = $this->totalUsers - $existingUsers;

        if ($usersToCreate > 0) {
            $this->createFactoryUsers($usersToCreate);
        }

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->command->info("\n✅ Successfully created " . User::count() . " users in {$executionTime} seconds");
    }

    private function createMainUsers(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'role' => 'manager',
        ]);

        User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'role' => 'user',
        ]);
    }

    private function createFactoryUsers($count): void
    {
        $chunks = ceil($count / $this->chunkSize);
        
        $this->command->info("Creating {$count} users in {$chunks} chunks...");
        $progressBar = $this->command->getOutput()->createProgressBar($chunks);

        for ($i = 0; $i < $chunks; $i++) {
            $chunkSize = min($this->chunkSize, $count - ($i * $this->chunkSize));
            
            User::factory()->count($chunkSize)->create();
            
            $progressBar->advance();
            
            // Clear memory every 5 chunks
            if ($i % 5 === 0) {
                gc_collect_cycles();
            }
        }

        $progressBar->finish();
    }
}