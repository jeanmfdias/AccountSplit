<?php

declare(strict_types=1);

namespace App\ApiResource;

use App\Enum\SplitType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class BillInput
{
    #[Groups(['bill:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $description = '';

    #[Groups(['bill:write'])]
    #[Assert\Positive]
    public int $amountCents = 0;

    #[Groups(['bill:write'])]
    #[Assert\NotBlank]
    public string $paidByParticipantId = '';

    #[Groups(['bill:write'])]
    #[Assert\NotNull]
    public ?\DateTimeImmutable $date = null;

    #[Groups(['bill:write'])]
    public SplitType $splitType = SplitType::Equal;

    /**
     * @var list<string>
     */
    #[Groups(['bill:write'])]
    #[Assert\Count(min: 1, minMessage: 'At least one participant is required.')]
    public array $participantIds = [];

    /**
     * @var array<string, int>
     */
    #[Groups(['bill:write'])]
    public array $customAmounts = [];

    /**
     * @var array<string, float>
     */
    #[Groups(['bill:write'])]
    public array $percentages = [];
}