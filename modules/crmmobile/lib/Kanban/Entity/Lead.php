<?php

namespace Bitrix\CrmMobile\Kanban\Entity;

class Lead extends KanbanEntity
{
	public function getEntityType(): string
	{
		return \CCrmOwnerType::LeadName;
	}
}
