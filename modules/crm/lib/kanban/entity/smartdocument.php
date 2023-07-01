<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;

class SmartDocument extends Dynamic
{
	protected function getDefaultAdditionalSelectFields(): array
	{
		$fields = parent::getDefaultAdditionalSelectFields();
		$fields[Item\SmartDocument::FIELD_NAME_NUMBER] = '';

		return $fields;
	}

	public function canAddItemToStage(string $stageId, \CCrmPerms $userPermissions, string $semantics = PhaseSemantics::UNDEFINED): bool
	{
		return false;
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}
}
