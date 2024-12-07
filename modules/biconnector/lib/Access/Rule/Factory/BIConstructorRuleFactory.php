<?php

namespace Bitrix\BIConnector\Access\Rule\Factory;

use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Access\Rule\Factory\RuleControllerFactory;

final class BIConstructorRuleFactory extends RuleControllerFactory
{
	protected const BASE_RULE = 'Base';
	protected const DASHBOARD_RULE = 'Dashboard';

	protected function getClassName(string $action, AccessibleController $controller): ?string
	{
		$action = str_replace(ActionDictionary::PREFIX, '', $action);
		$actionParts = explode('_', $action);
		$actionParts = array_map(static fn($el) => ucfirst(mb_strtolower($el)), $actionParts);
		$ruleClassName = $this->getNamespace($controller) . implode($actionParts) . static::SUFFIX;

		if (class_exists($ruleClassName))
		{
			return $ruleClassName;
		}

		if (in_array(self::DASHBOARD_RULE, $actionParts, true))
		{
			return $this->getNamespace($controller) . static::DASHBOARD_RULE . static::SUFFIX;
		}

		return $this->getNamespace($controller) . static::BASE_RULE . static::SUFFIX;
	}
}
