<?php

declare(strict_types=1);

namespace App\ApiResource;

final class ParticipantBalanceOutput
{
    public string $participantId   = '';
    public string $participantName = '';
    public int    $netCents        = 0;
    public string $formattedNet    = '';
}