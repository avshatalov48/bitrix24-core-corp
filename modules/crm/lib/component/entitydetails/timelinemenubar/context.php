<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

class Context
{
	private int $entityTypeId;
	private int $entityId;
	private ?int $entityCategoryId = null;
	private bool $isReadonly = false;
	protected string $guid = 'timeline';

	public function __construct(int $entityTypeId, int $entityId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function getEntityCategoryId(): ?int
	{
		return $this->entityCategoryId;
	}

	public function setEntityCategoryId(?int $entityCategoryId): self
	{
		$this->entityCategoryId = $entityCategoryId;

		return $this;
	}

	public function getGuid(): string
	{
		return $this->guid;
	}

	public function setGuid(string $guid): self
	{
		$this->guid = $guid;

		return $this;
	}


	public function isReadonly(): bool
	{
		return $this->isReadonly;
	}

	public function setIsReadonly(bool $isReadonly): self
	{
		$this->isReadonly = $isReadonly;

		return $this;
	}
}
