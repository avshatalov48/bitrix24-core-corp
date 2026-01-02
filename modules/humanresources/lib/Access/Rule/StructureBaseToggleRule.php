<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;
use Bitrix\Main\Access\Rule\AbstractRule;

final class StructureBaseToggleRule extends AbstractRule
{
	public function execute(\Bitrix\Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		if (!isset($params['PERMISSION_ID']))
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$permissionValue = $this->user->getPermission((string)$params['PERMISSION_ID']);

		return $permissionValue === PermissionDictionaryAlias::VALUE_YES;
	}
}