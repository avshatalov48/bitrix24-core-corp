<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Timer extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $userId = null,
		public readonly ?int $taskId = null,
		public readonly ?int $startedAtTs = null,
		public readonly ?int $seconds = null,
	)
	{

	}

	public function getId(): ?int
	{
		// for this entity userId is primary
		return $this->userId;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			userId: static::mapInteger($props, 'userId'),
			taskId: static::mapInteger($props, 'taskId'),
			startedAtTs: static::mapInteger($props, 'startedAtTs'),
			seconds: static::mapInteger($props, 'seconds'),
		);
	}

	public function toArray(): array
	{
		return [
			'userId' => $this->userId,
			'taskId' => $this->taskId,
			'startedAtTs' => $this->startedAtTs,
			'seconds' => $this->seconds,
		];
	}
}
