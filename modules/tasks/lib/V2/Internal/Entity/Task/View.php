<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class View extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $taskId = null,
		public readonly ?int $userId = null,
		public readonly ?int $viewedTs = null,
		public readonly ?bool $isRealView = null,
	)
	{

	}

	public function getId(): array
	{
		return [
			'TASK_ID' => $this->taskId,
			'USER_ID' => $this->userId,
		];
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			taskId: static::mapInteger($props, 'taskId'),
			userId: static::mapInteger($props, 'userId'),
			viewedTs: static::mapInteger($props, 'viewedTs'),
			isRealView: static::mapBool($props, 'isRealView'),
		);
	}

	public function toArray(): array
	{
		return [
			'taskId' => $this->taskId,
			'userId' => $this->userId,
			'viewedTs' => $this->viewedTs,
			'isRealView' => $this->isRealView,
		];
	}
}
