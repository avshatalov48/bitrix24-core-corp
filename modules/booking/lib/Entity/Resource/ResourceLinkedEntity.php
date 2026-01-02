<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Resource;

use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Model\ResourceLinkedEntityData\ResourceLinkedEntityDataInterface;
use Bitrix\Booking\Internals\Model\ResourceLinkedEntityData\ResourceLinkedEntityDataMapper;

class ResourceLinkedEntity implements EntityInterface
{
	private int|null $id = null;
	private int|null $entityId = null;
	private ResourceLinkedEntityType|null $entityType = null;
	private int|null $createdAt = null;
	private ResourceLinkedEntityDataInterface|null $data = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getEntityId(): ?int
	{
		return $this->entityId;
	}

	public function setEntityId(?int $entityId): self
	{
		$this->entityId = $entityId;

		return $this;
	}

	public function getEntityType(): ?ResourceLinkedEntityType
	{
		return $this->entityType;
	}

	public function setEntityType(?ResourceLinkedEntityType $entityType): self
	{
		$this->entityType = $entityType;

		return $this;
	}

	public function getCreatedAt(): ?int
	{
		return $this->createdAt;
	}

	public function setCreatedAt(?int $createdAt): self
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getData(): ?ResourceLinkedEntityDataInterface
	{
		return $this->data;
	}

	public function setData(?ResourceLinkedEntityDataInterface $data): self
	{
		$this->data = $data;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'entityId' => $this->entityId,
			'entityType' => $this->entityType->value,
			'data' => $this->data?->toArray(),
		];
	}

	public static function mapFromArray(array $props): EntityInterface
	{
		$linkedEntity = new self();

		if (($props['entityId'] ?? null) !== null)
		{
			$linkedEntity->setEntityId($props['entityId']);
		}

		$entityType = $props['entityType'] ?? null;

		if (!$entityType)
		{
			return $linkedEntity;
		}

		$type = ResourceLinkedEntityType::tryFrom($props['entityType']);
		if (!$type)
		{
			return $linkedEntity;
		}

		$linkedEntity->setEntityType($type);

		if ($props['data'] ?? null)
		{
			$linkedEntity->setData(ResourceLinkedEntityDataMapper::mapFromArray($type, $props['data']));
		}

		return $linkedEntity;
	}
}
