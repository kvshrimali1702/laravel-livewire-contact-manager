<?php

namespace App\Enums;

enum GenderOptions: int
{
    /**
     * Male
     */
    case MALE = 1;

    /**
     * Female
     */
    case FEMALE = 2;

    /**
     * Prefer not to say (default)
     */
    case PREFER_NOT_TO_SAY = 3;

    /**
     * Human-friendly label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::PREFER_NOT_TO_SAY => 'Prefer not to say',
        };
    }
}
