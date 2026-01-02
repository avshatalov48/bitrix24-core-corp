<?php

namespace Bitrix\Crm\Entity;

final class EntityEditorOptionParseResult
{
	public function __construct(
		private ?int $entityTypeId = null,
		private ?int $categoryId = null,
		private ?bool $isReturning = null,
	)
	{
	}

	public function entityTypeId(): ?int
	{
		return $this->entityTypeId;
	}

	public function isEntityTypeIdFound(): bool
	{
		return $this->entityTypeId() !== null;
	}

	public function setEntityTypeId(?int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function categoryId(): ?int
	{
		return $this->categoryId;
	}

	public function setCategoryId(?int $categoryId): self
	{
		$this->categoryId = $categoryId;

		return $this;
	}

	public function isReturning(): ?int
	{
		return $this->isReturning;
	}

	public function setIsReturning(?bool $isReturning): self
	{
		$this->isReturning = $isReturning;

		return $this;
	}
}
