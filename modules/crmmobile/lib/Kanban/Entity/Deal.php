<?php

namespace Bitrix\CrmMobile\Kanban\Entity;

class Deal extends KanbanEntity
{

	public function getEntityType(): string
	{
		return \CCrmOwnerType::DealName;
	}
}
