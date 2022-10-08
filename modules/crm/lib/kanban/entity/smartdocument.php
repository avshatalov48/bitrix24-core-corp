<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Item;

class SmartDocument extends Dynamic
{
	protected function getDefaultAdditionalSelectFields(): array
	{
		$fields = parent::getDefaultAdditionalSelectFields();
		$fields[Item\SmartDocument::FIELD_NAME_NUMBER] = '';

		return $fields;
	}

	public function canAddItemToStage(string $stageId, \CCrmPerms $userPermissions): bool
	{
		return false;
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}
}
