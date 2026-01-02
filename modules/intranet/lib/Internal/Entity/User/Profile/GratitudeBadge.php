<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Main\Entity\EntityInterface;

class GratitudeBadge implements EntityInterface
{
	public function __construct(
		public readonly int $gratitudeTypeId,
		public readonly int $count,
		public readonly string $title,
	)
	{}

	public function getId(): int
	{
		return $this->gratitudeTypeId;
	}
}
