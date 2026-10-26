<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Static lookup data. Production postcode/city data is imported
     * externally (see docs/OPERATIONS.md); help-type and status labels
     * below are editable defaults inferred from code (BACKLOG MOD-03).
     */
    public function run(): void
    {
        foreach ([
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Adminisztrátor'],
            ['id' => 6, 'name' => 'volunteer', 'display_name' => 'Vigyázó'],
            ['id' => 7, 'name' => 'requester', 'display_name' => 'Segítségkérő'],
        ] as $role) {
            DB::table('roles')->updateOrInsert(['id' => $role['id']], $role);
        }

        foreach ([
            'Bevásárlás',
            'Gyógyszerkiváltás',
            'Postai ügyintézés',
            'Kutyasétáltatás',
            'Ház körüli segítség',
            'Szállítás',
            'Beszélgetés, társaság',
            'Egyéb',
        ] as $name) {
            DB::table('help_types')->updateOrInsert(['name' => $name], ['name' => $name]);
        }

        foreach ([
            0 => 'Törölt',
            1 => 'Teljesítve',
            2 => 'Új',
            3 => 'Folyamatban',
            4 => 'Visszaigazolásra vár (önkéntes)',
            5 => 'Visszaigazolásra vár (kérelmező)',
        ] as $id => $name) {
            DB::table('request_statuses')->updateOrInsert(['id' => $id], ['id' => $id, 'name' => $name]);
        }
    }
}
