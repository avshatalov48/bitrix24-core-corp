<?php

namespace Bitrix\Tasks\Integration\IM\Notification;

class Tag
{
	private const UNIQUE_CASES = [
		'TASK_EXPIRED',
		'TASKS_ADDED_TO_FLOW_WITH_MANUAL_DISTRIBUTION',
		'TASKS_ADDED_TO_FLOW_WITH_HIMSELF_DISTRIBUTION',
	];
	private int $userId  = 0;
	private string $moduleName = 'TASKS';
	private string $entityCode = 'TASK';
	private string $actionName = '';
	private int $entityId = 0;
	private array $tasksIds = [];

	public function getNameWithSignature(): string
	{
		return (count($this->tasksIds) > 1)
			? $this->getName() . '|' . $this->getSignature()
			: $this->getName();
	}

	public function getName(): string
	{
		return ($this->isUnique())
			? $this->getSubName()
			: $this->getBaseName();
	}

	public function getSubName(): string
	{
		return ($this->actionName)
			? $this->getBaseName() . '|' . $this->actionName
			: $this->getBaseName();
	}

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;
		return $this;
	}

	public function setActionName(string $actionName): self
	{
		$this->actionName = $actionName;
		return $this;
	}

	public function setModuleName(string $moduleName): self
	{
		$this->moduleName = $moduleName;
		return $this;
	}

	public function setEntityCode(string $entityCode): self
	{
		$this->entityCode = $entityCode;
		return $this;
	}

	public function setTasksIds(array $tasksIds): self
	{
		$this->tasksIds = $tasksIds;
		return $this;
	}

	public function setEntityId(int $entityId): self
	{
		$this->entityId = $entityId;
		return $this;
	}

	private function getBaseName(): string
	{
		$name = $this->moduleName . '|' . $this->entityCode . '|' . $this->getFirstTaskId() . '|' . $this->userId;

		return ($this->entityId)
			? $name . '|' . $this->entityId
			: $name;
	}

	private function isUnique(): bool
	{
		return in_array($this->actionName, self::UNIQUE_CASES, true);
	}

	private function getFirstTaskId(): int
	{
		return $this->tasksIds[0] ?? 0;
	}

	private function getSignature(): string
	{
		return md5(implode(':', $this->tasksIds));
	}
}