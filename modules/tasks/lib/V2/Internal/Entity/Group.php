<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Validation\Rule\Min;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Group extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		#[Min(0)]
		public readonly ?int $id = null,
		public readonly ?string $name = null,
		public readonly ?File $image = null,
		/** @see \Bitrix\Socialnetwork\Item\Workgroup\Type */
		public readonly ?string $type = null,
		public readonly ?bool $isVisible = null,
	)
	{
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function isScrum(): bool
	{
		return $this->type === 'scrum';
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'image' => $this->image?->toArray(),
			'type' => $this->type,
			'isVisible' => $this->isVisible,
		];
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			name: static::mapString($props, 'name'),
			image: static::mapEntity($props, 'image', File::class),
			type: static::mapString($props, 'type'),
			isVisible: static::mapBool($props, 'isVisible'),
		);
	}
}
