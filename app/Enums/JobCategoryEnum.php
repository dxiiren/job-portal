<?php

namespace App\Enums;

enum JobCategoryEnum: string
{
    case IT = 'IT';
    case Finance = 'Finance';
    case Sales = 'Sales';
    case Marketing = 'Marketing';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
