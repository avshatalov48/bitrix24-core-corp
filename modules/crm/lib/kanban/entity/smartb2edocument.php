<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;

class SmartB2eDocument extends Dynamic
{
	protected function getDefaultAdditionalSelectFields(): array
	{
		$fields = parent::getDefaultAdditionalSelectFields();
		$fields[Item\SmartB2eDocument::FIELD_NAME_NUMBER] = '';

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
