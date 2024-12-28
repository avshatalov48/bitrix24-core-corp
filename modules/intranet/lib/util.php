<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Bitrix24\Integrator;
use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Intranet;
use Bitrix\Main;
use Bitrix\Bitrix24;
use Bitrix\Extranet;

Loc::loadMessages(__FILE__);

/**
 * Class Util
 * @package Bitrix\Intranet
 */
class Util
{
	const CP_BITRIX_PATH = 'https://bitrix24.team';

	private static array $userStatus = [];

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
			'ID', 'ASC',
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
				$langFromTemplate = $b24Languages;
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

	/**
	 * @deprecated use Intranet\Portal::getInstance()->getSettings()->getLogo()
	 * @return array
	 */
	public static function getClientLogo($force = false)
	{
		if ($result = Intranet\Portal::getInstance()->getSettings()->getLogo())
		{
			return [
				'logo' => $result['id'],
				'regular' => $result['id'],
				'retina' => 0,
			];
		}

		return [
			'logo' => null,
			'retina' => null,
		];
	}

	public static function getLogo24()
	{
		return Intranet\Portal::getInstance()->getSettings()->getLogo24();
	}

	public static function isIntranetUser(int $userId = null): bool
	{
		try
		{
			$userId ??= CurrentUser::get()->getId();

			return (new User($userId))->isIntranet();
		}
		catch (ArgumentOutOfRangeException $e)
		{
			return false;
		}
	}

	public static function isExtranetUser(int $userId = null): bool
	{
		$userId = $userId ?? Intranet\CurrentUser::get()->getId();
		if (Main\Loader::includeModule('extranet'))
		{
			return (new Extranet\User($userId))->isExtranet();
		}

		return false;
	}

	public static function getUserFieldListConfigUrl(string $moduleId, string $entityId = ''): Uri
	{
		if(empty($moduleId))
		{
			throw new ArgumentNullException('moduleId');
		}
		$url = 'configs/userfield_list.php';
		if(ModuleManager::isModuleInstalled('bitrix24'))
		{
			$url = 'settings/' . $url;
		}

		$url = new Uri(SITE_DIR . $url);
		$url->addParams([
			'moduleId' => $moduleId,
		]);
		if($entityId)
		{
			$url->addParams([
				'entityId' => $entityId,
			]);
		}

		return $url;
	}

	public static function getUserFieldDetailConfigUrl(string $moduleId, string $entityId, int $fieldId = 0): Uri
	{
		if(empty($moduleId))
		{
			throw new ArgumentNullException('moduleId');
		}
		if(empty($entityId))
		{
			throw new ArgumentNullException('entityId');
		}
		$url = 'configs/userfield.php';
		if(ModuleManager::isModuleInstalled('bitrix24'))
		{
			$url = 'settings/' . $url;
		}

		$url = new Uri(SITE_DIR . $url);
		$url->addParams([
			'moduleId' => $moduleId,
			'entityId' => $entityId,
		]);
		if($fieldId)
		{
			$url->addParams([
				'fieldId' => $fieldId,
			]);
		}

		return $url;
	}

	public static function checkIntegratorActionRestriction(array $params = [])
	{
		global $USER;
		static $currentIntegrator = null;

		$result = false;
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);

		if ($userId <= 0)
		{
			return $result;
		}

		if ($currentIntegrator === null)
		{
			$currentIntegrator = (
				Loader::includeModule('bitrix24')
				&& Integrator::isIntegrator($USER->getId())
			);
		}

		return !(
			$currentIntegrator
			&& Loader::includeModule('bitrix24')
			&& \CBitrix24::isPortalAdmin($userId)
			&& !Integrator::isIntegrator($userId)
		);
	}

	public static function isCurrentUserAdmin()
	{
		$currentUser = CurrentUser::get();

		if (
			Loader::includeModule("bitrix24") && \CBitrix24::isPortalAdmin($currentUser->getId())
			|| $currentUser->isAdmin()
		)
		{
			return true;
		}

		return false;
	}

	public static function setAdminRights($params)
	{
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$currentUserId = (!empty($params['currentUserId']) ? intval($params['currentUserId']) : 0);
		$isCurrentUserAdmin = !!$params['isCurrentUserAdmin'];

		if (
			$userId <= 0
			|| $currentUserId <= 0
		)
		{
			return false;
		}

		if (
			!(
				Loader::includeModule("bitrix24") && \CBitrix24::isPortalAdmin($currentUserId)
				|| $isCurrentUserAdmin
			)
		)
		{
			return false;
		}

		if (
			Loader::includeModule("bitrix24")
			&& \Bitrix\Bitrix24\Integrator::isIntegrator($currentUserId)
		)
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList([
			'select' => [ 'ID', 'UF_DEPARTMENT', 'ACTIVE' ],
			'filter' => [
				'=ID' => $userId
			],
		])->fetch();

		if (
			!is_array($userData['UF_DEPARTMENT']) // is extranet
			|| empty($userData['UF_DEPARTMENT'][0])
			|| $userData['ACTIVE'] !== "Y"
		)
		{
			return false;
		}

		$removeRightsFromCurrentAdmin = false;

		//groups for bitrix24 cloud
		if (
			Loader::includeModule('bitrix24') &&
			!\CBitrix24::isMoreAdminAvailable()
		)
		{
			$removeRightsFromCurrentAdmin = true;

			if (!Feature::isFeatureEnabled('delegation_admin_rights'))
			{
				return false;
			}
		}

		[ $employeesGroupId, $portalAdminGroupId ] = self::getGroupsId();

		$currentUserGroups = \CUser::getUserGroup($userId);
		foreach ($currentUserGroups as $groupKey => $group)
		{
			if ($group == $employeesGroupId)
			{
				unset($currentUserGroups[$groupKey]);
			}
		}
		$currentUserGroups[] = "1";
		$currentUserGroups[] = $portalAdminGroupId;
		$user = new \CUser();
		$user->update($userId, ['GROUP_ID' => $currentUserGroups]);

		$event = new Event(
			'intranet',
			'onUserAdminRigths',
			[
				'originatorId' => $currentUserId,
				'userId' => $userId,
				'type' => "setAdminRigths"
			]
		);
		$event->send();

		//remove rights from current admin because of limit
		if ($removeRightsFromCurrentAdmin)
		{
			$currentAdminGroups = \CUser::getUserGroup($currentUserId);
			foreach ($currentAdminGroups as $groupKey => $group)
			{
				if ($group == 1 || $group == $portalAdminGroupId)
				{
					unset($currentAdminGroups[$groupKey]);
				}
			}
			$currentAdminGroups[] = $employeesGroupId;

			$user->Update($currentUserId, ['GROUP_ID' => $currentAdminGroups]);
		}

		return true;
	}

	public static function removeAdminRights($params)
	{
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$currentUserId = (!empty($params['currentUserId']) ? intval($params['currentUserId']) : 0);
		$isCurrentUserAdmin = !!$params['isCurrentUserAdmin'];

		if (
			!(
				Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin($currentUserId)
				|| $isCurrentUserAdmin
			)
		)
		{
			return false;
		}

		if (
			Loader::includeModule("bitrix24")
			&& \Bitrix\Bitrix24\Integrator::isIntegrator($currentUserId)
		)
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList(array(
			'select' => [ 'ID', 'UF_DEPARTMENT', 'ACTIVE' ],
			'filter' => [
				'=ID' => $userId
			],
		))->fetch();

		if (
			!is_array($userData['UF_DEPARTMENT']) // is extranet
			|| empty($userData['UF_DEPARTMENT'][0])
		)
		{
			return false;
		}

		[ $employeesGroupId, $portalAdminGroupId ] = self::getGroupsId();

		$currentUserGroups = \CUser::getUserGroup($userId);
		foreach ($currentUserGroups as $groupKey => $group)
		{
			if ($group == 1 || $group == $portalAdminGroupId)
			{
				unset($currentUserGroups[$groupKey]);
			}
		}
		$currentUserGroups[] = $employeesGroupId;
		$user = new \CUser();
		$user->Update($userId, ['GROUP_ID' => $currentUserGroups]);

		$event = new Event(
			'intranet',
			'onUserAdminRigths',
			[
				'originatorId' => $currentUserId,
				'userId' => $userId,
				'type' => "removeAdminRigths"
			]
		);
		$event->send();

		return true;
	}

	public static function deactivateUser($params)
	{
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$currentUserId = (!empty($params['currentUserId']) ? intval($params['currentUserId']) : 0);
		$isCurrentUserAdmin = !!$params['isCurrentUserAdmin'];

		if (
			Loader::includeModule("bitrix24")
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled("user_dismissal")
			&& !\Bitrix\Bitrix24\Integrator::isIntegrator($userId)
		)
		{
			return false;
		}

		if (
			!(
				Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin($currentUserId)
				|| $isCurrentUserAdmin
			)
		)
		{
			return false;
		}

		$user = new \CUser;
		$res = $user->Update($userId, ['ACTIVE' => 'N', 'CONFIRM_CODE' => '']);

		if (!$res)
		{
			return false;
		}

		return true;
	}

	public static function activateUser($params)
	{
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$currentUserId = (!empty($params['currentUserId']) ? intval($params['currentUserId']) : 0);
		$isCurrentUserAdmin = !!$params['isCurrentUserAdmin'];

		if (
		!(
			Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin($currentUserId)
			|| $isCurrentUserAdmin
		)
		)
		{
			return false;
		}

		$updateFields = ['ACTIVE' => 'Y'];

		$res = InvitationTable::getList([
			'select' => ['INITIALIZED'],
			'filter' => [
				'USER_ID' => $userId
			]
		])->fetch();

		if ($res && isset($res['INITIALIZED']) && $res['INITIALIZED'] === 'N')
		{
			$updateFields['CONFIRM_CODE'] = Random::getString(8, true);
		}

		$user = new \CUser;
		$res = $user->Update($userId, $updateFields);

		if (!$res)
		{
			return false;
		}

		if (!empty($updateFields['CONFIRM_CODE']) && $userId > 0)
		{
			$deactivateUser = new User($userId);
			Invitation::fullSyncCounterByUser($deactivateUser->fetchOriginatorUser());
		}

		return true;
	}

	public static function getGroupsId()
	{
		$employeesGroupId = "";
		$portalAdminGroupId = "";

		if (ModuleManager::isModuleInstalled("bitrix24"))
		{
			$employeesGroupId = "11";
			$portalAdminGroupId = "12";
		}
		else
		{
			$res = \CGroup::GetList('', '', ["STRING_ID" => implode("|", ["EMPLOYEES_".SITE_ID, "PORTAL_ADMINISTRATION_".SITE_ID])]);
			while ($group = $res->fetch())
			{
				if ($group["STRING_ID"] === "EMPLOYEES_".SITE_ID)
				{
					$employeesGroupId = $group["ID"];
				}
				elseif ($group["STRING_ID"] === "PORTAL_ADMINISTRATION_".SITE_ID)
				{
					$portalAdminGroupId = $group["ID"];
				}
			}
		}

		return [ $employeesGroupId, $portalAdminGroupId ];
	}

	public static function getUserStatus($userId)
	{
		if (!empty(self::$userStatus[$userId]))
		{
			return self::$userStatus[$userId];
		}

		$status = '';
		$currentUser = Intranet\CurrentUser::get();
		$isCurrentUser = $currentUser->getId() == $userId;

		if ($isCurrentUser)
		{
			$user = \CUser::getById($userId)->fetch();
		}
		else
		{
			$user = \Bitrix\Main\UserTable::query()
				->setSelect(['ID', 'ACTIVE', 'CONFIRM_CODE', 'EXTERNAL_AUTH_ID', 'UF_DEPARTMENT'])
				->where('ID', '=', $userId)
				->fetch();
		}

		if ($user)
		{
			if ($user['ACTIVE'] == 'N')
			{
				$status = 'fired';
			}
			else if (!empty($user['CONFIRM_CODE']))
			{
				$status = 'invited';
			}
			else if ($user["EXTERNAL_AUTH_ID"] === 'email')
			{
				$status = 'email';
			}
			elseif (in_array($user["EXTERNAL_AUTH_ID"], ['shop', 'sale', 'saleanonymous']))
			{
				$status = 'shop';
			}
			else if (Main\Loader::includeModule("bitrix24") && Bitrix24\Integrator::isIntegrator($userId))
			{
				$status = 'integrator';
			}
			else if ($isCurrentUser)
			{
				if ($currentUser->isAdmin())
				{
					$status = 'admin';
				}
				else if (self::isIntranetUser($userId))
				{
					$status = 'employee';
				}
				elseif (
					Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById((int)$userId)
				)
				{
					$status = 'collaber';
				}
				else
				{
					$status = 'extranet';
				}
			}
			else
			{
				$groups = \CUser::getUserGroup($userId);

				if (in_array(1, $groups))
				{
					$status = "admin";
				}
				else
				{
					$status = "employee";

					if (empty($user['UF_DEPARTMENT'])
						|| !is_array($user['UF_DEPARTMENT'])
						|| empty($user['UF_DEPARTMENT'][0])
					)
					{
						$extranetGroupId = (
						Loader::includeModule('extranet')
							? intval(\CExtranet::getExtranetUserGroupId())
							: 0
						);

						if (
							$extranetGroupId
							&& in_array($extranetGroupId, $groups)
						)
						{
							if (
								Extranet\Service\ServiceContainer::getInstance()
									->getCollaberService()
									->isCollaberById((int)$userId)
							)
							{
								$status = 'collaber';
							}
							else
							{
								$status = 'extranet';
							}
						}
					}
				}
			}
		}

		self::$userStatus[$userId] = $status;

		return $status;
	}

	public static function getAppsInstallationConfig(int $userId): array
	{
		$result = [];
		$appActivity = [
			'APP_WINDOWS_INSTALLED' => \CUserOptions::GetOption('im', 'WindowsLastActivityDate', '', $userId),
			'APP_MAC_INSTALLED' => \CUserOptions::GetOption('im', 'MacLastActivityDate', '', $userId),
			'APP_IOS_INSTALLED' => \CUserOptions::GetOption('mobile', 'iOsLastActivityDate', '', $userId),
			'APP_ANDROID_INSTALLED' => \CUserOptions::GetOption('mobile', 'AndroidLastActivityDate', '', $userId),
			'APP_LINUX_INSTALLED' => \CUserOptions::GetOption('im', 'LinuxLastActivityDate', '', $userId),
		];
		$appsActivityTimeout = self::getAppsActivityTimeout();

		foreach ($appActivity as $key => $lastActivity)
		{
			if ((int)$lastActivity <= 0 || $lastActivity < time() - $appsActivityTimeout)
			{
				$result[$key] = false;
			}
			else
			{
				$result[$key] = true;
			}
		}

		return $result;
	}

	public static function getAppsActivityTimeout(): int
	{
		$defaultLastActivityTimeout = 30 * 24 * 60 * 60; // 30 days

		return (int)Option::get('intranet', 'app_last_activity_ttl', $defaultLastActivityTimeout);
	}
}

