<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Seeded listings are the existing market, not new ones: without this each
    // live listing would queue a (no-op) alerting job.
    use WithoutModelEvents;

    public function run(): void
    {
        // The demo user that the auth stub resolves every API request as.
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@street.example',
        ]);

        $branches = Branch::factory(8)->create();

        foreach ($branches as $branch) {
            Listing::factory(rand(18, 30))->live()->for($branch)->create();
            Listing::factory(rand(3, 6))->underOffer()->for($branch)->create();
            Listing::factory(rand(4, 8))->sold()->for($branch)->create();
            Listing::factory(rand(2, 5))->for($branch)->create(); // drafts
        }
    }
}
