<?php
namespace Bitrix\Disk\Integration;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class Bitrix24Manager
{
	/**
	 * Tells if module bitrix24 is installed.
	 *
	 * @return bool
	 */
	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}

	/**
	 * Tells if user has access to entity by different restriction on B24.
	 *
	 * @param string $entityType Entity type.
	 * @param int $userId User id.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isAccessEnabled($entityType, $userId)
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return \CBitrix24BusinessTools::isToolAvailable($userId, $entityType);
	}

	public static function checkAccessEnabled($entityType, $userId)
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return \CBitrix24BusinessTools::isToolAvailable($userId, $entityType, false);
	}

	public static function isUserRestricted(int $userId): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		return \Bitrix\Bitrix24\Limits\User::isUserRestricted($userId);
	}

	/**
	 * Returns true if tariff for this portal is not free.
	 *
	 * @return bool
	 */
	public static function isLicensePaid()
	{
		if(Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::IsLicensePaid();
		}

		return false;
	}

	/**
	 * Init javascript license popup.
	 *
	 * @param string $featureGroupName
	 */
	public static function initLicenseInfoPopupJS($featureGroupName = "")
	{
		if(Loader::includeModule('bitrix24'))
		{
			\CBitrix24::initLicenseInfoPopupJS($featureGroupName);
		}
	}

	/**
	 * @param $feature
	 *
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isFeatureEnabled($feature)
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::isFeatureEnabled($feature);
		}

		return true;
	}

	public static function getFeatureVariable($feature)
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::getVariable($feature);
		}

		return null;
	}

	public static function filterJsAction($feature, $jsAction, $skip = false)
	{
		if ($skip || self::isFeatureEnabled($feature))
		{
			return $jsAction;
		}

		['title' => $title, 'descr' => $descr] = self::processFeatureToMessageCode($feature);

		return "BX.Bitrix24.LicenseInfoPopup.show('{$feature}', '{$title}', '{$descr}')";
	}

	private static function processFeatureToMessageCode($feature): array
	{
		if ($feature === 'disk_manual_external_link')
		{
			$feature = 'disk_external_link';
		}

		$featureInMessage = mb_strtoupper($feature);

		return [
			'title' => GetMessageJS("DISK_B24_FEATURES_{$featureInMessage}_1_TITLE"),
			'descr' => GetMessageJS("DISK_B24_FEATURES_{$featureInMessage}_1_DESCR")
		];
	}
}