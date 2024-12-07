<?php

namespace Bitrix\BIConnector\Access\Filter\Factory;

use Bitrix\BIConnector\Access\Filter\DashboardViewFilter;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Access\Filter\AccessFilter;
use Bitrix\Main\Access\Filter\FilterFactory;

final class BIConstructorFilterFactory implements FilterFactory
{
	private array $actionToFilterClassMap = [];

	/**
	 * @param string $action
	 * @param AccessibleController $controller
	 *
	 * @return AccessFilter|null
	 */
	public function createFromAction(string $action, AccessibleController $controller): ?AccessFilter
	{
		$filterClassName = $this->getFilterClassByAction($action);
		if (!$filterClassName)
		{
			return null;
		}

		return new $filterClassName($controller);
	}

	/**
	 * @return array[]
	 */
	private static function getMap(): array
	{
		return [
			DashboardViewFilter::class => [
				ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
			],
		];
	}

	/**
	 * @param string $action
	 *
	 * @return string|null
	 */
	private function getFilterClassByAction(string $action): ?string
	{
		if (!$this->actionToFilterClassMap)
		{
			foreach (self::getMap() as $filterClass => $itemActionNames)
			{
				foreach ($itemActionNames as $itemAction)
				{
					$this->actionToFilterClassMap[$itemAction] = $filterClass;
				}
			}
		}

		return $this->actionToFilterClassMap[$action] ?? null;
	}
}
