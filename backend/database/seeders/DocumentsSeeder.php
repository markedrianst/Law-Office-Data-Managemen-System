<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Documents;

class DocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['type' => 'Complaint',        'category' => 'Pleading',       'requires_approval' => true,  'sort_order' => 1],
            ['type' => 'Answer',           'category' => 'Pleading',       'requires_approval' => true,  'sort_order' => 2],
            ['type' => 'Motion',           'category' => 'Pleading',       'requires_approval' => true,  'sort_order' => 3],
            ['type' => 'Affidavit',        'category' => 'Pleading',       'requires_approval' => true,  'sort_order' => 4],
            ['type' => 'Demand Letter',    'category' => 'Letter',         'requires_approval' => true,  'sort_order' => 5],
            ['type' => 'SPA',              'category' => 'Letter',         'requires_approval' => true,  'sort_order' => 6],
            ['type' => 'Notice',           'category' => 'Letter',         'requires_approval' => false, 'sort_order' => 7],
            ['type' => 'Subpoena',         'category' => 'Court Issuance', 'requires_approval' => false, 'sort_order' => 8],
            ['type' => 'Court Order',      'category' => 'Court Issuance', 'requires_approval' => false, 'sort_order' => 9],
            ['type' => 'Proof of Service', 'category' => 'Evidence',       'requires_approval' => false, 'sort_order' => 10],
            ['type' => 'Registry Receipt', 'category' => 'Evidence',       'requires_approval' => false, 'sort_order' => 11],
            ['type' => "ID's",             'category' => 'Evidence',       'requires_approval' => false, 'sort_order' => 12],
            ['type' => 'TCT Title',        'category' => 'Evidence',       'requires_approval' => false, 'sort_order' => 13],
            ['type' => 'Receipts',         'category' => 'Evidence',       'requires_approval' => false, 'sort_order' => 14],
            ['type' => 'Others',           'category' => 'Other',          'requires_approval' => false, 'sort_order' => 9999],
        ];

        foreach ($types as $type) {
            Documents::updateOrCreate(
                ['type' => $type['type']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}