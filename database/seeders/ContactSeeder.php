<?php

namespace Database\Seeders;

use Database\Factories\ContactFactory;
use Database\Factories\EmailFactory;
use Database\Factories\PhoneFactory;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContactFactory::new()
            ->count(50)
            ->has(PhoneFactory::new()->count(rand(1, 3)), 'phones')
            ->has(EmailFactory::new()->count(rand(0, 2)), 'emails')
            ->create();
    }
}
