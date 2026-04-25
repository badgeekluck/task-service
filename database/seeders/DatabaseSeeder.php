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
        $admin = User::factory()->create([
            'name'  => 'Harun',
            'email' => 'harun@test.com',
            // şifre: password
        ]);

        Task::factory(5)->forUser($admin)->pending()->create();
        Task::factory(3)->forUser($admin)->inProgress()->create();
        Task::factory(2)->forUser($admin)->completed()->create();
        Task::factory(2)->forUser($admin)->overdue()->create();
        Task::factory(1)->forUser($admin)->critical()->create();

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
