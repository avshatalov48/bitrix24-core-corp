<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Item;
use Bitrix\Crm\Restriction\RestrictionManager;

class ObserversFieldRestrictionManager extends FieldRestrictionManagerBase
{
	final public function hasRestrictions(): bool
	{
		if (!isset($this->entityTypeId))
		{
			return false;
		}

		return RestrictionManager::getObserversFieldRestriction($this->entityTypeId)->isExceeded();
	}

	final public function getJsCallback(): string
	{
		if (!isset($this->entityTypeId))
		{
			return '';
		}

		return RestrictionManager::getObserversFieldRestriction($this->entityTypeId)->prepareInfoHelperScript();
	}

	final protected function isFieldRestricted(string $fieldName): bool
	{
		return in_array($fieldName, ['OBSERVER_IDS', Item::FIELD_NAME_OBSERVERS], true);
	}
}
