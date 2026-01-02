<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;
use Bitrix\Tasks\V2\Internal\Entity\Task\Gantt\LinkType;

class GanttLink extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $taskId = null,
		public readonly ?int $dependentId = null,
		public readonly ?LinkType $type = null,
		public readonly ?int $creatorId = null,
		public readonly ?int $direction = null,
	)
	{

	}
	public function getId(): ?int
	{
		return $this->taskId;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			taskId: static::mapInteger($props, 'taskId'),
			dependentId: static::mapInteger($props, 'dependentId'),
			type: static::mapBackedEnum($props, 'type', LinkType::class),
			creatorId: static::mapInteger($props, 'creatorId'),
			direction: static::mapInteger($props, 'direction'),
		);
	}

	public function toArray(): array
	{
		return [
			'taskId' => $this->taskId,
			'dependentId' => $this->dependentId,
			'type' => $this->type?->value,
			'creatorId' => $this->creatorId,
			'direction' => $this->direction,
		];
	}
}
