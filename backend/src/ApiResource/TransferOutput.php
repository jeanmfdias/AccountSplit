<?php

declare(strict_types=1);

namespace App\ApiResource;

final class TransferOutput
{
    public string $fromParticipantId   = '';
    public string $fromParticipantName = '';
    public string $toParticipantId     = '';
    public string $toParticipantName   = '';
    public int    $amountCents         = 0;
    public string $formattedAmount     = '';
}