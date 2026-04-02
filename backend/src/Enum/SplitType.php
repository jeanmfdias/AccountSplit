<?php

declare(strict_types=1);

namespace App\Enum;

enum SplitType: string
{
    case Equal = 'equal';
    case Percentage = 'percentage';
    case Custom = 'custom';
}