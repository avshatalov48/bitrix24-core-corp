<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Restriction\RestrictionManager;

class ObserversFieldRestrictionManager extends FieldRestrictionManagerBase
{
	final public function hasRestrictions(): bool
	{
		return RestrictionManager::getDealObserversFieldRestriction()->isExceeded();
	}

	final public function getJsCallback(): string
	{
		return RestrictionManager::getDealObserversFieldRestriction()->prepareInfoHelperScript();
	}

	final protected function isFieldRestricted(string $fieldName): bool
	{
		return $fieldName === 'OBSERVER_IDS';
	}
}
