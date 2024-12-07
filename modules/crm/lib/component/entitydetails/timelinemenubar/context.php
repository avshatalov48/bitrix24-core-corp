<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

use Bitrix\Main\Application;

class Context
{
	private string $region;
	private bool $isReadonly = false;

	protected string $guid = 'timeline';

	public function __construct(
		private readonly int $entityTypeId,
		private readonly int $entityId,
		private ?int $entityCategoryId = null
	)
	{
		$this->region = Application::getInstance()->getContext()->getLanguage() ?? 'ru';
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

	public function getRegion(): string
	{
		return $this->region;
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
