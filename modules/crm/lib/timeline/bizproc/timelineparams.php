<?php

namespace Bitrix\Crm\Timeline\Bizproc;

class TimelineParams
{
	private int $entityTypeId;
	private int $entityId;
	private int $associatedEntityTypeId;
	private int $associatedEntityId;
	private array $settings;
	private int $authorId;
	private string $workflowId;

	public function __construct(string $workflowId, int $entityTypeId, int $entityId, array $settings, int $authorId)
	{
		$this->workflowId = $workflowId;
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;
		$this->settings = $settings;
		$this->authorId = $authorId;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function getSettings(): array
	{
		return $this->settings;
	}

	public function getAuthorId(): int
	{
		return $this->authorId;
	}

	public function setEntityTypeId(int $entityTypeId): void
	{
		$this->entityTypeId = $entityTypeId;
	}

	public function setEntityId(int $entityId): void
	{
		$this->entityId = $entityId;
	}

	public function setSettings(array $settings): void
	{
		$this->settings = $settings;
	}

	public function setAuthorId(int $authorId): void
	{
		$this->authorId = $authorId;
	}

	public function hasAssociatedEntityTypeId(): bool
	{
		return !empty($this->associatedEntityTypeId);
	}

	public function getAssociatedEntityTypeId(): int
	{
		return $this->associatedEntityTypeId;
	}

	public function setAssociatedEntityTypeId(int $associatedEntityTypeId): void
	{
		$this->associatedEntityTypeId = $associatedEntityTypeId;
	}

	public function hasAssociatedEntityId(): bool
	{
		return !empty($this->associatedEntityId);
	}

	public function getAssociatedEntityId(): int
	{
		return $this->associatedEntityId;
	}

	public function setAssociatedEntityId(int $associatedEntityId): void
	{
		$this->associatedEntityId = $associatedEntityId;
	}

	public function setWorkflowId(string $workflowId): void
	{
		$this->workflowId = $workflowId;
	}

	public function getWorkflowId(): string
	{
		return $this->workflowId;
	}
}
