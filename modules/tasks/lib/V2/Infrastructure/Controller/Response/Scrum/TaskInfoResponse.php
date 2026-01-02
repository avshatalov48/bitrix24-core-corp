<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Response\Scrum;

use Bitrix\Tasks\V2\Internal\Entity;

class TaskInfoResponse
{
	public function __construct(
		public readonly ?string $storyPoints = null,
		public readonly ?Entity\Epic $epic = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'storyPoints' => $this->storyPoints,
			'epic' => $this->epic?->toArray(),
		];
	}
}
