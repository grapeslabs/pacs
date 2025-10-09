<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    public function run(int $count = 15): void
    {
        $organizations = Organization::factory()
            ->count($count)
            ->create();

        $this->command->info("✅ Создано организаций: {$organizations->count()}");
    }
}
