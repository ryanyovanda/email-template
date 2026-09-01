<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

/**
 * Default credit packages shown on the Buy Credits page. Idempotent: matches on
 * name so re-running never duplicates. Admins can edit or remove these later in
 * Admin → Settings.
 */
class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Starter', 'credits' => 100, 'price' => 15000, 'sort_order' => 1],
            ['name' => 'Popular', 'credits' => 300, 'price' => 39000, 'sort_order' => 2],
            ['name' => 'Pro', 'credits' => 750, 'price' => 89000, 'sort_order' => 3],
        ];

        foreach ($packages as $package) {
            CreditPackage::query()->updateOrCreate(
                ['name' => $package['name']],
                [
                    'credits' => $package['credits'],
                    'price' => $package['price'],
                    'sort_order' => $package['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
