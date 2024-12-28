<?php

namespace Bitrix\Crm\Integration\Intranet\SystemPageProvider;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Integration\Intranet\SystemPageProvider;
use Bitrix\Crm\Service\Container;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSection;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSectionPage;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

final class PermissionsPage extends SystemPageProvider
{
	public const CODE = 'perms';
	private const SORT = 999997; // before activities

	public static function getComponent(string $pageSettings, Uri $url): ?Component
	{
		$settingsArr = explode(self::SEPARATOR, $pageSettings);
		$sectionCode = $settingsArr[1] ?? null;
		if ($sectionCode === null)
		{
			return null;
		}

		$componentParams = [
			'sectionCode' => $sectionCode,
			'criterion' => self::matchCriterion($url),
			'isExternal' => true,
		];

		return (new Component())
			->setComponentName('bitrix:crm.config.perms.wrapper')
			->setComponentParams($componentParams)
			->setComponentTemplate('')
		;
	}

	public static function getPageInstance(CustomSection $section): ?CustomSectionPage
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$title = Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM');
		$settings = self::getSettings($section);

		return (new CustomSectionPage())
			->setCode(self::CODE)
			->setTitle($title)
			->setSort(self::SORT)
			->setSettings($settings)
			->setModuleId('crm')
			->setDisabledInCtrlPanel(false)
		;
	}

	private static function getSettings(CustomSection $section): string
	{
		return implode(self::SEPARATOR, [self::CODE, $section->getCode()]);
	}

	private static function matchCriterion(Uri $url): ?string
	{
		$code = self::CODE;
		$pattern = "|/{$code}/(?'criterion'\w+)/?|u";
		preg_match($pattern, $url->getPath(), $matches);

		return $matches['criterion'] ?? null;
	}

	public static function isPageAvailable(CustomSection $section): bool
	{
		$automatedSolution =  Container::getInstance()->getAutomatedSolutionManager()->getExistingAutomatedSolutions()[$section->getId()] ?? null;
		return
			parent::isPageAvailable($section)
			&& $automatedSolution
			&& Feature::enabled(Feature\PermissionsLayoutV2::class)
			&& Container::getInstance()->getUserPermissions()->isAutomatedSolutionAdmin($automatedSolution['ID'])
		;
	}
}
