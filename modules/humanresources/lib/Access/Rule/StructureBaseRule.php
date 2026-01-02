<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Service\Container;

class StructureBaseRule extends AbstractRule
{
	public const PERMISSION_ID_KEY = 'PERMISSION_ID';

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (
			!($item instanceof NodeModel)
			|| !is_array($params)
			|| !isset($params[self::PERMISSION_ID_KEY])
		)
		{
			return false;
		}

		$node = $item->getNode();
		if (!$node || !$node->id)
		{
			return false;
		}

		$permissionId = (string)$params[self::PERMISSION_ID_KEY];
		if (
			$node->type !== NodeEntityType::DEPARTMENT
			|| !PermissionDictionary::isDepartmentVariablesPermission($permissionId)
		)
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$permissionValue = $this->user->getPermission($params[self::PERMISSION_ID_KEY]);
		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_NONE)
		{
			return false;
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			return true;
		}

		if ($node->type === NodeEntityType::TEAM)
		{
			return false;
		}

		$accessNodeRepository = Container::getAccessNodeRepository();
		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
		{
			return $accessNodeRepository->isDepartmentUser($item->getId(), $this->user->getUserId());
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS)
		{
			return $accessNodeRepository->isDepartmentUser($item->getId(), $this->user->getUserId(), true);
		}

		return false;
	}
}