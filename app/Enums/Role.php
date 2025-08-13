<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case SELLER = 'seller';

    public function name(): string
    {
        return match ($this) {
            self::ADMIN => 'Администратор',
            self::SELLER => 'Менеджер по продажам',
        };
    }

    public static function stringCases(): array
    {
        return [
            self::ADMIN->value,
            self::SELLER->value,
        ];
    }
}
