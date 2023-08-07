<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Restriction\RestrictionManager;

class ActivityFieldRestrictionManager extends FieldRestrictionManagerBase
{
	final public function hasRestrictions(): bool
	{
		return RestrictionManager::getActivityFieldRestriction()->isExceeded();
	}

	final public function getJsCallback(): string
	{
		return RestrictionManager::getActivityFieldRestriction()->prepareInfoHelperScript();
	}

	final protected function isFieldRestricted(string $fieldName): bool
	{
		return $fieldName === 'ACTIVITY_COUNTER';
	}
}
