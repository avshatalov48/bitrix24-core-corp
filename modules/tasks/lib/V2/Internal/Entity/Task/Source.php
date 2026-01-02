<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;
use Bitrix\Tasks\V2\Internal\Entity\ValueObjectInterface;

class Source implements ValueObjectInterface
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?string $type = null,
		public readonly ?array $data = null
	)
	{
	}

	public function toArray(): array
	{
		return [
			'type' => $this->type,
			'data' => $this->data,
		];
	}

	public static function mapFromArray(array $props = []): static
	{
		return new static(
			type: static::mapString($props, 'type'),
			data: [
				'entityId' => static::mapInteger($props, 'entityId'),
				'subEntityId' => static::mapInteger($props, 'subEntityId'),
			],
		);
	}
}
