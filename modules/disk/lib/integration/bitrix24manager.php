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

	public static function isFeatureTrialable($feature): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::isFeatureTrialable($feature);
		}

		return false;
	}

	public static function getTrialFeatureInfo($feature): ?array
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::getTrialFeatureInfo($feature);
		}

		return [];
	}

	public static function getTrialEditionInfo(): ?array
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::getTrialEditionInfo('demo');
		}

		return [];
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
		$map = [
			'disk_manual_external_link' => 'limit_office_share_file',
			'disk_manual_external_folder' => 'limit_office_share_link',
			'disk_file_sharing' => 'limit_office_files_access_permissions',
			'disk_folder_sharing' => 'limit_office_folders_access_permissions',
			'disk_folder_rights' => 'limit_office_disk_folders_access_rights',
			'disk_file_rights' => 'limit_office_disk_files_access_rights',
			'disk_common_storage' => 'limit_company_common_disk',
		];

		$helpdeskId = $map[$feature];

		if ($feature === 'disk_manual_external_folder')
		{
			$feature = 'disk_manual_external_link';
		}
		if ($feature === 'disk_folder_rights')
		{
			$feature = 'disk_folder_sharing';
		}
		if ($feature === 'disk_file_rights')
		{
			$feature = 'disk_file_sharing';
		}

		if ($skip || self::isFeatureEnabled($feature))
		{
			return $jsAction;
		}


		return "BX.UI.InfoHelper.show('{$helpdeskId}')";
	}
}