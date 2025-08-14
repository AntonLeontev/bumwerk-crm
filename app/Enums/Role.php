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
        $result = [];

        foreach (self::cases() as $case) {
            $result[] = $case->value;
        }

        return $result;
    }
}
