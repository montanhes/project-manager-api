<?php

namespace App\Enums;

enum TaskDifficulty: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;

    public function text(): string
    {
        return match ($this) {
            self::LOW => 'baixa',
            self::MEDIUM => 'média',
            self::HIGH => 'alta',
        };
    }

    public function points(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 4,
            self::HIGH => 12,
        };
    }
}
