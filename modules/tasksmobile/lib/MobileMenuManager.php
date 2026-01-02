<?php

namespace Bitrix\TasksMobile;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Menu\Manager\MobileMenuManager as BaseMobileMenuManager;
use Bitrix\Mobile\Tab\Manager;
use Bitrix\Mobile\Menu\MenuList;
use Bitrix\Mobile\Menu\Analytics;

class MobileMenuManager extends BaseMobileMenuManager
{
	public static function onMobileMenuStructureBuilt(array $menu, $context): array
	{
		if (($context instanceof Context) && \Bitrix\Main\Loader::includeModule('intranet'))
		{
			$manager = new Manager();
			$active = array_keys($manager->getActiveTabs());

			if (in_array('task', $active, true))
			{
				return $menu;
			}

			$items = [];
			if (\Bitrix\TasksMobile\Settings::getInstance()->isTaskFlowAvailable())
			{
				$items[] = self::prepareFlowItem();
			}

			if (!in_array('projects', $active, true) && \Bitrix\Intranet\Settings\Tools\ToolsManager::getInstance()->checkAvailabilityByToolId('projects'))
			{
				$items[] = self::prepareProjectsItem();
			}

			return self::addMenuItems($menu, MenuList::SECTION_TASKS, $items);
		}

		return $menu;
	}

	private static function prepareProjectsItem(): array
	{
		return [
			'id' => 'projects',
			'sort' => 200,
			'title' => Loc::getMessage('MENU_TASKS_SECTION_PROJECTS'),
			'imageName' => 'kanban',
			'counter' => 'projects',
			'path' => '/projects/',
			'params' => [
				'title' => Loc::getMessage('MENU_TASKS_SECTION_PROJECTS'),
				'analytics' => Analytics::projects(),
			],
		];
	}

	private static function prepareFlowItem(): array
	{
		return [
			'id' => 'flow',
			'sort' => 300,
			'title' => Loc::getMessage('MENU_TASKS_SECTION_FLOW'),
			'imageName' => 'bottleneck',
			'counter' => 'flow',
			'path' => '/flow/',
			'params' => [
				'title' => Loc::getMessage('MENU_TASKS_SECTION_FLOW'),
				'analytics' => Analytics::flows(),
			],
		];
	}
}
