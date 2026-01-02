<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class File extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		public readonly ?string $src = null,
		public readonly ?string $name = null,
		public readonly ?int $width = null,
		public readonly ?int $height = null,
		public readonly ?int $size = null,
		public readonly ?string $subDir = null,
		public readonly ?string $contentType = null,
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
			src: static::mapString($props, 'src'),
			name: static::mapString($props, 'name'),
			width: static::mapInteger($props, 'width'),
			height: static::mapInteger($props, 'height'),
			size: static::mapInteger($props, 'size'),
			subDir: static::mapString($props, 'subDir'),
			contentType: static::mapString($props, 'contentType'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'src' => $this->src,
			'name' => $this->name,
			'width' => $this->width,
			'height' => $this->height,
			'size' => $this->size,
			'subDir' => $this->subDir,
			'contentType' => $this->contentType,
		];
	}
}
