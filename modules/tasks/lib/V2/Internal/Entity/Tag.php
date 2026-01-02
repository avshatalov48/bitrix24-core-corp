<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Tag extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		#[NotEmpty]
		public readonly ?string $name = null,
		public readonly ?User $owner = null,
		public readonly ?Group $group = null,
		public readonly ?Task $task = null,
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
			name: static::mapString($props, 'name'),
			owner: static::mapEntity($props, 'owner', User::class),
			group: static::mapEntity($props, 'group', Group::class),
			task: static::mapEntity($props, 'task', Task::class),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'owner' => $this->owner?->toArray(),
			'group' => $this->group?->toArray(),
			'task' => $this->task?->toArray(),
		];
	}
}
