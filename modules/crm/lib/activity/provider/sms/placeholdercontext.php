<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

class PlaceholderContext
{
	public static function createInstance(int $entityTypeId, ?int $entityCategoryId = null): self
	{
		return new self($entityTypeId, $entityCategoryId);
	}

	private function __construct(private int $entityTypeId, private ?int $entityCategoryId = null)
	{

	}

	public function toArray(): array
	{
		return [
			'entityTypeId' => $this->entityTypeId,
			'entityCategoryId' => $this->entityCategoryId,
		];
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getEntityCategoryId(): ?int
	{
		return $this->entityCategoryId;
	}
}
