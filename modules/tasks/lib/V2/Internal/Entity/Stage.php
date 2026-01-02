<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Validation\Rule\Min;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Stage extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		#[Min(0)]
		public readonly ?int $id = null,
		public readonly ?string $title = null,
		public readonly ?string $color = null,
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
			title: static::mapString($props, 'title'),
			color: static::mapString($props, 'color'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'color' => $this->color,
		];
	}
}
