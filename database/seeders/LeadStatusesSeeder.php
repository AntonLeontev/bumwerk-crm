<?php

namespace Database\Seeders;

use App\Models\LeadStatus;
use Illuminate\Database\Seeder;

class LeadStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LeadStatus::create([
            'name' => 'Не разобрано',
            'color' => '#0000003a',
            'position' => 0,
            'is_final' => false,
            'is_system' => true,
        ]);

        LeadStatus::create([
            'name' => 'Успешно завершено',
            'color' => '#0000003a',
            'position' => 255,
            'is_final' => true,
            'is_system' => true,
        ]);

        LeadStatus::create([
            'name' => 'Отказ',
            'color' => '#0000003a',
            'position' => 255,
            'is_final' => true,
            'is_system' => true,
        ]);

        LeadStatus::create([
            'name' => 'В работе',
            'color' => '#10b9813a',
            'position' => 1,
            'is_final' => false,
            'is_system' => false,
        ]);
        LeadStatus::create([
            'name' => 'Ожидаем визита',
            'color' => '#f59e0b3a',
            'position' => 2,
            'is_final' => false,
            'is_system' => false,
        ]);
        LeadStatus::create([
            'name' => 'Ожидаем оплату',
            'color' => '#22c55e3a',
            'position' => 3,
            'is_final' => false,
            'is_system' => false,
        ]);
    }
}
