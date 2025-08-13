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
            'is_start' => true,
            'is_win' => false,
            'is_loose' => false,
        ]);

        LeadStatus::create([
            'name' => 'Успешно завершено',
            'color' => '#22c55e',
            'position' => 255,
            'is_final' => true,
            'is_system' => true,
            'is_start' => false,
            'is_win' => true,
            'is_loose' => false,
        ]);

        LeadStatus::create([
            'name' => 'Отказ',
            'color' => '#EF5350',
            'position' => 255,
            'is_final' => true,
            'is_system' => true,
            'is_start' => false,
            'is_win' => false,
            'is_loose' => true,
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
