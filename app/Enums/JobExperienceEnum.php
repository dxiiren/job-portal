<?php

namespace App\Enums;

enum JobExperienceEnum: string
{
    case Entry = 'entry';
    case Intermediate = 'intermediate';
    case Senior = 'senior';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
