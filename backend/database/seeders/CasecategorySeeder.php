<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CaseCategory;

class CasecategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Criminal',      'is_active' => true, 'sort_order' => 1],
            ['name' => 'Annulment',     'is_active' => true, 'sort_order' => 2],
            ['name' => 'Civil',         'is_active' => true, 'sort_order' => 3],
            ['name' => 'Land Issues',   'is_active' => true, 'sort_order' => 4],
            ['name' => 'Land Transfer', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Pending',       'is_active' => true, 'sort_order' => 6],
            ['name' => 'Admin',         'is_active' => true, 'sort_order' => 7],
            ['name' => 'Other',         'is_active' => true, 'sort_order' => 9999],
        ];

        foreach ($categories as $category) {
            CaseCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}