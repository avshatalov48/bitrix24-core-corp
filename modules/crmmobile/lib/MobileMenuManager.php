<?php

namespace Bitrix\CrmMobile;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Menu\Manager\MobileMenuManager as BaseMobileMenuManager;
use Bitrix\Mobile\Menu\MenuList;
use Bitrix\Mobile\Menu\Analytics;

class MobileMenuManager extends BaseMobileMenuManager
{
	public static function onMobileMenuStructureBuilt(array $menu, $context): array
	{
		if (
			method_exists(\Bitrix\Crm\Service\UserPermissions::class, 'entityType')
				? \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canReadSomeItemsInCrmOrAutomatedSolutions()
				: \CCrmPerms::IsAccessEnabled()
		)
		{
			$activityItem = self::prepareActivityItem();

			$menu =  self::addMenuItem($menu, MenuList::SECTION_CRM, $activityItem);
		}

		return $menu;
	}

	private static function prepareActivityItem(): array
	{
		return [
			'id' => 'activity',
			'sort' => 300,
			'title' => Loc::getMessage('MENU_CRM_SECTION_ACTIVITY'),
			'imageName' => 'my_deals',
			'counter' => 'activity',
			'params' => [
				'url' => '/mobile/crm/activity/list.php',
				'id' => 'crm_activity_list',
				'analytics' => Analytics::crmActivity(),
			],
		];
	}
}
