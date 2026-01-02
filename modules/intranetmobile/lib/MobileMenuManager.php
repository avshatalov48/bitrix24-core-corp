<?php

namespace Bitrix\IntranetMobile;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Menu\Manager\MobileMenuManager as BaseMobileMenuManager;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Mobile\Menu\MenuList;
use Bitrix\Mobile\Menu\Analytics;

class MobileMenuManager extends BaseMobileMenuManager
{
	public static function onMobileMenuStructureBuilt(array $menu, $context): array
	{
		if (!($context instanceof Context))
		{
			return $menu;
		}

		if (!$context->isCollaber && ToolsManager::getInstance()->checkAvailabilityByToolId('workgroups'))
		{
			$groupsItem = self::prepareGroups($context->userId);
			$menu = self::addMenuItem($menu, MenuList::SECTION_TEAMWORK, $groupsItem);
		}

		return $menu;
	}

	private static function prepareGroups(int $userId): array
	{
		$workgroupsComponentVersion = \Bitrix\MobileApp\Janative\Manager::getComponentVersion("workgroups");
		$siteDir = SITE_DIR;
		$siteId = SITE_ID;
		$workgroupUrlTemplate = \Bitrix\Mobile\Project\Helper::getProjectNewsPathTemplate([
			'siteDir' => $siteDir,
		]);
		$workgroupCalendarWebPathTemplate = \Bitrix\Mobile\Project\Helper::getProjectCalendarWebPathTemplate([
			'siteDir' => $siteDir,
			'siteId' => $siteId,
		]);

		$features = implode(',', \Bitrix\Mobile\Project\Helper::getMobileFeatures());
		$mandatoryFeatures = implode(',', \Bitrix\Mobile\Project\Helper::getMobileMandatoryFeatures());
		$menuName = Loc::getMessage('MENU_BITRIX24_SECTION_GROUPS');

		return [
			'id' => 'groups',
			'sort' => 600,
			'title' => Loc::getMessage('MENU_BITRIX24_SECTION_GROUPS'),
			'imageName' => 'three_persons',
			'counter' => 'groups',
			'params' => [
				'onclick' => <<<JS
					ComponentHelper.openList({
						name: 'workgroups',
						object: 'list',
						version: "{$workgroupsComponentVersion}",
						componentParams: {
							siteId: "{$siteId}",
							siteDir: "{$siteDir}",
							pathTemplate: "{$workgroupUrlTemplate}",
							calendarWebPathTemplate: "{$workgroupCalendarWebPathTemplate}",
							features: "{$features}",
							mandatoryFeatures: "{$mandatoryFeatures}",
							currentUserId: "{$userId}"
						},
						widgetParams: {
							titleParams: {
								text: "{$menuName}",
								 type: "section",
								 },
							useSearch: false,
							doNotHideSearchResult: true
						}
					});
				JS,
				'analytics' => Analytics::groups(),
			]
		];
	}
}
