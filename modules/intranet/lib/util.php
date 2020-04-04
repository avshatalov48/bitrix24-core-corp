<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Util
 * @package Bitrix\Intranet
 */
class Util
{
	public static function getDepartmentEmployees($params)
	{
		if (!is_array($params["DEPARTMENTS"]))
		{
			$params["DEPARTMENTS"] = array($params["DEPARTMENTS"]);
		}

		if (
			isset($params["RECURSIVE"])
			&& $params["RECURSIVE"] == "Y"
		)
		{
			$params["DEPARTMENTS"] = \CIntranetUtils::getIBlockSectionChildren($params["DEPARTMENTS"]);
		}

		$filter = array(
			'UF_DEPARTMENT' => $params["DEPARTMENTS"]
		);

		if (
			isset($params["ACTIVE"])
			&& $params["ACTIVE"] == "Y"
		)
		{
			$filter['ACTIVE'] = 'Y';
		}

		if (
			isset($params["CONFIRMED"])
			&& $params["CONFIRMED"] == "Y"
		)
		{
			$filter['CONFIRM_CODE'] = false;
		}

		if (
			!empty($params["SKIP"])
			&& intval($params["SKIP"]) > 0
		)
		{
			$filter['!ID'] = intval($params["SKIP"]);
		}

		$select = (
			!empty($params["SELECT"])
			&& is_array($params["SELECT"])
				? array_merge(array('ID'), $params["SELECT"])
				: array('*', 'UF_*')
		);

		$userResult = \CUser::getList(
			$by = 'ID', $order = 'ASC',
			$filter,
			array(
				'SELECT' => $select,
				'FIELDS' => $select
				)
		);

		return $userResult;
	}

	/**
	 * Returns IDs of users who are in the departments and sub-departments linked to site (multi-portal)
	 * @param array $params
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	*/
	public static function getEmployeesList($params = array())
	{
		$result = array();

		if (
			(
				empty($params["SITE_ID"])
				&& empty($params["DEPARTMENTS"])
			)
			|| !ModuleManager::isModuleInstalled('intranet')
		)
		{
			return $result;
		}

		$userResult = false;
		$allUsers = false;

		if (!empty($params["SITE_ID"]))
		{
			$siteRootDepartmentId = intval(Option::get('main', 'wizard_departament', false, $params["SITE_ID"]));
			if ($siteRootDepartmentId <= 0)
			{
				$allUsers = true;

				$structureIblockId = Option::get('intranet', 'iblock_structure', 0);
				if (
					Loader::includeModule('iblock')
					&& $structureIblockId > 0
				)
				{
					$filter = array(
						"=ACTIVE" => "Y",
						"CONFIRM_CODE" => false,
						"!=UF_DEPARTMENT" => false
					);

					if (!empty($params["SKIP"]))
					{
						$filter['!ID'] = intval($params["SKIP"]);
					}

					$userResult = \Bitrix\Main\UserTable::getList(array(
						'order' => array(),
						'filter' => $filter,
						'select' => array("ID", "EMAIL", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN")
					));
				}
			}
			else
			{
				if (!isset($params["DEPARTMENTS"]))
				{
					$params["DEPARTMENTS"] = array();
				}
				$params["DEPARTMENTS"][] = $siteRootDepartmentId;
			}
		}

		if (
			!$allUsers
			&& !empty($params["DEPARTMENTS"])
		)
		{
			$userResult = \Bitrix\Intranet\Util::getDepartmentEmployees(array(
				'DEPARTMENTS' => $params["DEPARTMENTS"],
				'RECURSIVE' => 'Y',
				'ACTIVE' => 'Y',
				'CONFIRMED' => 'Y',
				'SKIP' => (!empty($params["SKIP"]) ? $params["SKIP"] : false),
				'SELECT' => array("ID", "EMAIL", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN")
			));
		}

		if ($userResult)
		{
			while ($user = $userResult->fetch())
			{
				$result[$user["ID"]] = array(
					"ID" => $user["ID"],
					"NAME_FORMATTED" => \CUser::formatName(\CSite::getNameFormat(null, $params["SITE_ID"]), $user, true),
					"EMAIL" => $user["EMAIL"]
				);
			}
		}

		return $result;
	}

	public static function getLanguageList()
	{
		$list = array();
		$langFromTemplate = array();

		if (\Bitrix\Main\ModuleManager::isModuleInstalled("intranet"))
		{
			global $b24Languages;
			$fileName = \Bitrix\Main\Application::getDocumentRoot() . getLocalPath('templates/bitrix24', BX_PERSONAL_ROOT) . "/languages.php";
			if (\Bitrix\Main\IO\File::isFileExists($fileName))
			{
				include_once $fileName;
			}
			if (isset($b24Languages) && is_array($b24Languages))
			{
				$langFromTemplate = \Bitrix\Main\Text\Encoding::convertEncoding($b24Languages, 'UTF-8', SITE_CHARSET);
			}
		}

		$langDir = \Bitrix\Main\Application::getDocumentRoot() . '/bitrix/modules/intranet/lang/';
		$dir = new \Bitrix\Main\IO\Directory($langDir);
		if ($dir->isExists())
		{
			foreach($dir->getChildren() as $childDir)
			{
				if (!$childDir->isDirectory())
				{
					continue;
				}

				$list[] = $childDir->getName();
			}

			if (count($list) > 0)
			{
				$listDb = \Bitrix\Main\Localization\LanguageTable::getList(array(
					'select' => array('LID', 'NAME'),
					'filter' => array(
						'=LID' => $list,
						'=ACTIVE' => 'Y'
					),
					'order' => array('SORT' => 'ASC')
				));
				$list = array();
				while ($item = $listDb->fetch())
				{
					$list[$item['LID']] = isset($langFromTemplate[$item['LID']])? $langFromTemplate[$item['LID']]: $item['NAME'];
				}
			}
		}

		return $list;
	}

	public static function getClientLogo($force = false)
	{
		if (!$force && Loader::includeModule('bitrix24'))
		{
			if (!\Bitrix\Bitrix24\Feature::isFeatureEnabled("set_logo"))
			{
				return array(
					'logo' => 0,
					'retina' => 0,
				);
			}
		}

		$regular = (int) Option::get('bitrix24', 'client_logo', 0);
		$retina = (int) Option::get('bitrix24', 'client_logo_retina', 0);

		return array(
			'logo' => $regular ?: $retina,
			'regular' => $regular,
			'retina' => $retina,
		);
	}

	public static function getLogo24($force = false)
	{
		$logo = '24';

		if ($force)
		{
			return $logo;
		}

		if (Loader::includeModule('bitrix24'))
		{
			if (!\Bitrix\Bitrix24\Feature::isFeatureEnabled("remove_logo24"))
			{
				return $logo;
			}
		}

		if (Option::get('bitrix24', 'logo24show', 'Y') == 'N')
		{
			$logo = '';
		}

		return $logo;
	}

	public static function isIntranetUser(int $userId = null): bool
	{
		if (is_null($userId))
		{
			global $USER;
			$userId = $USER->GetID();
			if ($userId <= 0)
			{
				return false;
			}

			$accessManager = new \CAccess;
			$accessManager->UpdateCodes();

			$codes = $USER->GetAccessCodes();
		}
		else
		{
			if ($userId <= 0)
			{
				return false;
			}

			$accessManager = new \CAccess;
			$accessManager->UpdateCodes(['USER_ID' => $userId]);

			$codes = \CAccess::GetUserCodesArray($userId);
		}

		foreach ($codes as $code)
		{
			if (preg_match('/^D[0-9]+$/', $code))
			{
				return true;
			}
		}

		return false;
	}

	public static function isExtranetUser(int $userId = null): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('extranet'))
		{
			return false;
		}

		$extranetGroupId = (int)\Bitrix\Main\Config\Option::get('extranet', 'extranet_group', 0);
		if (!$extranetGroupId)
		{
			return false;
		}

		if (is_null($userId))
		{
			global $USER;
			$userId = $USER->GetID();
			if ($userId <= 0)
			{
				return false;
			}

			$userGroups = array_map('intval', $USER->GetUserGroupArray());
		}
		else
		{
			if ($userId <= 0)
			{
				return false;
			}

			$userGroups = \Bitrix\Main\UserTable::getUserGroupIds($userId);
		}

		return in_array($extranetGroupId, $userGroups, true);
	}

}

