<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    protected $signature = 'app:create-token';

    public function handle()
    {
        $user = User::where('email', 'aner-anton@yandex.ru')->first();
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;
        $this->info('API token: '.$token);
    }
}
