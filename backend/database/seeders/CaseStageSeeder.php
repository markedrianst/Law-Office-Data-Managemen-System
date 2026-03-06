<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CaseStage;

class CaseStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'Intake / Client Interview', 'order' => 1, 'is_active' => true],
            ['name' => 'For Document Preparation',  'order' => 2, 'is_active' => true],
            ['name' => 'For Lawyer Review',          'order' => 3, 'is_active' => true],
            ['name' => 'For Filing',                 'order' => 4, 'is_active' => true],
            ['name' => 'Filed / Pending',            'order' => 5, 'is_active' => true],
            ['name' => 'Hearing / Proceedings',      'order' => 6, 'is_active' => true],
            ['name' => 'For Decision / Resolution',  'order' => 7, 'is_active' => true],
            ['name' => 'Closed',                     'order' => 8, 'is_active' => true],
        ];

        foreach ($stages as $stage) {
            CaseStage::updateOrCreate(
                ['name' => $stage['name']],
                $stage
            );
        }
    }
}