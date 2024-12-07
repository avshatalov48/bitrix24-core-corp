<?php

namespace Bitrix\HumanResources\Access\Rule\Factory;

use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Access\Rule\Factory\RuleControllerFactory;
use Bitrix\HumanResources\Access\StructureActionDictionary;

class RuleFactory extends RuleControllerFactory
{
	protected const STRUCTURE_BASE_RULE = 'StructureBase';

	protected function getClassName(string $action, AccessibleController $controller): ?string
	{
		$actionName = StructureActionDictionary::getActionName($action);
		if (!$actionName)
		{
			return null;
		}

		$actionName = explode('_', $actionName);
		$actionName = array_map(fn($el) => ucfirst(mb_strtolower($el)), $actionName);
		$ruleClass = $this->getNamespace($controller) . implode($actionName) . static::SUFFIX;

		if (class_exists($ruleClass))
		{
			return $ruleClass;
		}

		if (array_key_exists($action, StructureActionDictionary::getActionPermissionMap()))
		{
			return $this->getNamespace($controller) . static::STRUCTURE_BASE_RULE . static::SUFFIX;
		}

		return null;
	}
}