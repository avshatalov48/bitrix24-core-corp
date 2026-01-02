<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

class DiskFile extends AbstractEntity
{
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
		public readonly ?array $customData = null
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
			type: $props['type'] ?? null,
			name: $props['name'] ?? null,
			size: $props['size'] ?? null,
			width: $props['width'] ?? null,
			height: $props['height'] ?? null,
			isImage: $props['isImage'] ?? null,
			isVideo: $props['isVideo'] ?? null,
			treatImageAsFile: $props['treatImageAsFile'] ?? null,
			downloadUrl: $props['downloadUrl'] ?? null,
			serverPreviewUrl: $props['serverPreviewUrl'] ?? null,
			serverPreviewWidth: $props['serverPreviewWidth'] ?? null,
			serverPreviewHeight: $props['serverPreviewHeight'] ?? null,
			customData: $props['customData'] ?? null
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
			'customData' => $this->customData
		];
	}
}
