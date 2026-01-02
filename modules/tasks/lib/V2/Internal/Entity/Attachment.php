<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Attachment extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		public readonly ?string $fileId = null,
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
			fileId: static::mapString($props, 'fileId'),
		);
	}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'fileId' => $this->fileId,
		];
	}
}
