<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Restriction\RestrictionManager;

class ClientFieldRestrictionManager extends FieldRestrictionManagerBase
{
	final public function hasRestrictions(): bool
	{
		return RestrictionManager::getDealClientFieldsRestriction()->isExceeded();
	}

	final public function getJsCallback(): string
	{
		return RestrictionManager::getDealClientFieldsRestriction()->prepareInfoHelperScript();
	}

	final protected function isFieldRestricted(string $fieldName): bool
	{
		return (
			(
				mb_strpos($fieldName, 'CONTACT_') === 0
				|| mb_strpos($fieldName, 'COMPANY_') === 0
			)
			&& !in_array($fieldName, ['CONTACT_ID', 'COMPANY_ID'])
		);
	}
}
