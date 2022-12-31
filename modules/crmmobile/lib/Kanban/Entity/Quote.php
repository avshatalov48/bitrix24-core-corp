<?php


namespace Bitrix\CrmMobile\Kanban\Entity;


class Quote extends KanbanEntity
{
	public function getEntityType(): string
	{
		return \CCrmOwnerType::QuoteName;
	}
}
