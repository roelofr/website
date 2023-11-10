<?php

declare(strict_types=1);

namespace App\Enums\Models;

enum BotQuoteType: string
{
    case UNKNOWN = 'unknown';
    case QUOTE = 'quote';
    case FACT = 'fact';
}
