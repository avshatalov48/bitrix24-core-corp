<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Entity;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;
use Bitrix\Tasks\V2\Internal\Entity\User;

class DiskFile extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly null|int|string $id = null,
		public readonly null|int|string $serverId = null,
		public readonly ?string $type = null,
		public readonly ?string $name = null,
		public readonly ?int $size = null,
		public readonly ?int $width = null,
		public readonly ?int $height = null,
		public readonly ?bool $isImage = null,
		public readonly ?bool $isVideo = null,
		public readonly ?bool $treatImageAsFile = null,
		public readonly ?string $downloadUrl = null,
		public readonly ?string $serverPreviewUrl = null,
		public readonly ?int $serverPreviewWidth = null,
		public readonly ?int $serverPreviewHeight = null,
		public readonly ?array $customData = null,
		public readonly ?array $viewerAttrs = null,
		public readonly ?User $owner = null,
	)
	{

	}

	public function getId(): int|string|null
	{
		return $this->id;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: $props['serverFileId'] ?? null,
			serverId: $props['serverId'] ?? null,
			type: static::mapString($props, 'type'),
			name: static::mapString($props, 'name'),
			size: static::mapInteger($props, 'size'),
			width: static::mapInteger($props, 'width'),
			height: static::mapInteger($props, 'height'),
			isImage: static::mapBool($props, 'isImage'),
			isVideo: static::mapBool($props, 'isVideo'),
			treatImageAsFile: static::mapBool($props, 'treatImageAsFile'),
			downloadUrl: static::mapString($props, 'downloadUrl'),
			serverPreviewUrl: static::mapString($props, 'serverPreviewUrl'),
			serverPreviewWidth: static::mapInteger($props, 'serverPreviewWidth'),
			serverPreviewHeight: static::mapInteger($props, 'serverPreviewHeight'),
			customData: static::mapArray($props, 'customData'),
			viewerAttrs: static::mapArray($props, 'viewerAttrs'),
			owner: static::mapEntity($props, 'owner', User::class),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'type' => $this->type,
			'name' => $this->name,
			'size' => $this->size,
			'width' => $this->width,
			'height' => $this->height,
			'isImage' => $this->isImage,
			'isVideo' => $this->isVideo,
			'treatImageAsFile' => $this->treatImageAsFile,
			'downloadUrl' => $this->downloadUrl,
			'serverPreviewUrl' => $this->serverPreviewUrl,
			'serverPreviewWidth' => $this->serverPreviewWidth,
			'serverPreviewHeight' => $this->serverPreviewHeight,
			'customData' => $this->customData,
			'viewerAttrs' => $this->viewerAttrs,
			'owner' => $this->owner?->toArray(),
		];
	}
}
