<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class HistoryLog extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		public readonly ?int $createdDateTs = null,
		public readonly ?int $userId = null,
		public readonly ?int $taskId = null,
		public readonly ?string $field = null,
		public readonly mixed $fromValue = null,
		public readonly mixed $toValue = null,
	)
	{

	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			createdDateTs: static::mapInteger($props, 'createdDateTs'),
			userId: static::mapInteger($props, 'userId'),
			taskId: static::mapInteger($props, 'taskId'),
			field: static::mapString($props, 'field'),
			fromValue: static::mapMixed($props, 'fromValue'),
			toValue: static::mapMixed($props, 'toValue'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'createdDateTs' => $this->createdDateTs,
			'userId' => $this->userId,
			'taskId' => $this->taskId,
			'field' => $this->field,
			'fromValue' => $this->fromValue,
			'toValue' => $this->toValue,
		];
	}
}
