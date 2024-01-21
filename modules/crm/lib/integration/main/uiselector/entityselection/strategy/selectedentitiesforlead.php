<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection\Strategy;

class SelectedEntitiesForLead extends SelectedEntities
{
	public function getEntities(array $items): array
	{
		$entityType = $this->entity->getType();

		return $items[$entityType] ?? [];
	}
}
