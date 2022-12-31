<?php

namespace Bitrix\CrmMobile\Kanban\Entity;

class Dynamic extends KanbanEntity
{
	protected $entityType;

	public function getEntityType(): string
	{
		return $this->entityType;
	}

	public function setEntityType(string $entityType): Dynamic
	{
		$this->entityType = $entityType;
		return $this;
	}
}

