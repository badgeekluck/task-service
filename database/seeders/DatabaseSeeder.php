<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'harun@test.com'],
            [
                'name' => 'Harun',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        Task::factory(5)->for($admin)->pending()->create();
        Task::factory(3)->for($admin)->inProgress()->create();
        Task::factory(2)->for($admin)->completed()->create();
        Task::factory(2)->for($admin)->overdue()->create();
        Task::factory(1)->for($admin)->critical()->create();

        $other = User::factory()->create([
            'name'  => 'Other User',
            'email' => 'other@test.com',
            // şifre: password
        ]);

        Task::factory(5)->forUser($other)->create();

        // 8 rastgele kullanıcı, her biri 3-8 task ile
        User::factory(8)->create()->each(function (User $user) {
            Task::factory(fake()->numberBetween(3, 8))->forUser($user)->create();
        });

        $this->command->info('Seed tamamlandı:');
        $this->command->info('  harun@test.com / password');
        $this->command->info('  other@test.com / password');
    }
}
