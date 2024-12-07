<?php

namespace Bitrix\Sign\Access\Rule\Factory;

use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Access\Rule\Factory\RuleControllerFactory;
use Bitrix\Sign\Access\ActionDictionary;

class SignRuleFactory extends RuleControllerFactory
{
	protected const BASE_RULE = 'Base';

	protected function getClassName(string $action, AccessibleController $controller): ?string
	{
		$actionName = ActionDictionary::getActionName($action);
		if (!$actionName)
		{
			return null;
		}

		$action = explode('_', $actionName);
		$action = array_map(fn($el) => ucfirst(mb_strtolower($el)), $action);

		$ruleClass = $this->getNamespace($controller) . implode($action) . static::SUFFIX;

		if (class_exists($ruleClass))
		{
			return $ruleClass;
		}

		return $this->getNamespace($controller) . static::BASE_RULE . static::SUFFIX;
	}
}
