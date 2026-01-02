<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Validation\Rule\Min;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Flow extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		#[Min(0)]
		public readonly ?int $id = null,
		public readonly ?string $name = null,
		public readonly ?int $efficiency = null,
		public readonly ?Group $group = null,
	)
	{
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'efficiency' => $this->efficiency,
			'group' => $this->group?->toArray(),
		];
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			name: static::mapString($props, 'name'),
			efficiency: static::mapInteger($props, 'efficiency'),
			group: static::mapEntity($props, 'group', Group::class),
		);
	}
}
