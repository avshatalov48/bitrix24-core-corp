<?php


namespace Bitrix\CrmMobile\Kanban\Entity;


class SmartInvoice extends KanbanEntity
{
	public function getEntityType(): string
	{
		return \CCrmOwnerType::SmartInvoiceName;
	}
}
