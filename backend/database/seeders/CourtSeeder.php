<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CourtOffice;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            ['name' => 'RTC Branch 16, Urdaneta City',            'type' => 'Court',      'address' => 'Urdaneta City', 'sort_order' => 1,    'is_active' => true],
            ['name' => 'MTC Capas, Tarlac',                       'type' => 'Court',      'address' => 'Capas, Tarlac', 'sort_order' => 2,    'is_active' => true],
            ['name' => 'Office of the City Prosecutor – Angeles', 'type' => 'Prosecutor', 'address' => 'Angeles City',  'sort_order' => 3,    'is_active' => true],
            ['name' => 'DARAB',                                   'type' => 'Agency',     'address' => null,            'sort_order' => 4,    'is_active' => true],
            ['name' => 'NLRC',                                    'type' => 'Agency',     'address' => null,            'sort_order' => 5,    'is_active' => true],
            ['name' => 'SEC',                                     'type' => 'Agency',     'address' => null,            'sort_order' => 6,    'is_active' => true],
            ['name' => 'BIR RDO ___',                             'type' => 'Agency',     'address' => null,            'sort_order' => 7,    'is_active' => true],
            ['name' => 'Others',                                   'type' => 'Others',      'address' => null,            'sort_order' => 9999, 'is_active' => true],
        ];

        foreach ($courts as $court) {
            CourtOffice::updateOrCreate(
                ['name' => $court['name']],
                $court
            );
        }
    }
}