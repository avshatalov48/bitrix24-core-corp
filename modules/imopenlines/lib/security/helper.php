<?php

namespace Bitrix\ImOpenlines\Security;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Helper
{
	private static $showWidgetLink = null;
	/**
	 * @param int $userId
	 * @param string $permission
	 * @return array|null Returns array of owner user ids if there is limit and null if query should not be limited.
	 */
	public static function getAllowedUserIds($userId, $permission)
	{
		$result = array();
		switch ($permission)
		{
			case Permissions::PERMISSION_NONE:
				$result = array();
				break;
			case Permissions::PERMISSION_SELF:
				$result = array($userId);
				break;
			case Permissions::PERMISSION_DEPARTMENT:
				$result = self::getUserColleagues($userId);
				break;
			case Permissions::PERMISSION_ANY:
				$result = null;
				break;
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public static function getCurrentUserId()
	{
		return (int)$GLOBALS['USER']->GetID();
	}

	/**
	 * @param $userId
	 * @return array
	 */
	public static function getUserColleagues($userId)
	{
		if(!Loader::includeModule('intranet'))
			return array();

		$result = array();
		$cursor = \CIntranetUtils::getDepartmentColleagues($userId, true);

		while ($row = $cursor->Fetch())
		{
			$result[] = $row['ID'];
		}
		return $result;
	}
	
	public static function canCurrentUserModifyLine()
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canModifyLines();
	}
	
	public static function canCurrentUserModifyConnector()
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canModifyConnectors();
	}
	
	public static function clearMenuCache()
	{
		\Bitrix\Main\Application::getInstance()->getTaggedCache()->clearByTag('bitrix:menu');
	}
	
	public static function canUse()
	{
		if(!Loader::includeModule('bitrix24'))
			return true;

		return Feature::isFeatureEnabled('imopenlines_security');
	}
	
	public static function isMainMenuEnabled()
	{
		return (
			self::isStatisticsMenuEnabled() ||
			self::isSettingsMenuEnabled() ||
			self::isLinesMenuEnabled() ||
			self::isCrmWidgetEnabled()
		);
	}
	
	public static function isCrmWidgetEnabled()
	{
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') && !$GLOBALS['USER']->CanDoOperation('bitrix24_config'))
		{
			return false;
		}
			
		if (is_null(self::$showWidgetLink))
		{
			self::$showWidgetLink = false;
			if (\Bitrix\Main\Loader::includeModule('crm') && \CCrmPerms::IsAccessEnabled())
			{
				$crmPerms = new \CCrmPerms($GLOBALS["USER"]->GetID());
				if (!$crmPerms->HavePerm('BUTTON', BX_CRM_PERM_NONE))
				{
					self::$showWidgetLink = true;
				}
			}
		}
		
		return self::$showWidgetLink;
	}
	public static function isLinesMenuEnabled()
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canViewLines();
	}
	
	public static function isStatisticsMenuEnabled()
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canViewStatistics();
	}
	
	public static function isSettingsMenuEnabled()
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canModifySettings();
	}
	
	public static function getTrialText()
	{
		$title = Loc::getMessage("IMOL_TRIAL_S_TITLE");
		$text = '<p>'.Loc::getMessage("IMOL_TRIAL_S_P1").'</p><p>'.Loc::getMessage("IMOL_TRIAL_S_P2").'</p> 
			 <ul class="hide-features-list">
			 	<li class="hide-features-list-item">'.Loc::getMessage("IMOL_TRIAL_S_F1").'</li>
				<li class="hide-features-list-item">'.Loc::getMessage("IMOL_TRIAL_S_F2").'</li>
				<li class="hide-features-list-item">'.Loc::getMessage("IMOL_TRIAL_S_F3").'</li> 
				<li class="hide-features-list-item">'.Loc::getMessage("IMOL_TRIAL_S_F4").'</li>';

		return array('TITLE' => $title, 'TEXT' => $text);
	}
	
	public static function installRolesAgent()
	{
		$checkCursor = \Bitrix\ImOpenlines\Model\RoleTable::getList(array('limit' => 1));
		if($checkCursor->fetch())
			return "";
		
		$defaultRoles = array(
			'ADMIN' => array(
				'NAME' => GetMessage('IMOL_ROLE_ADMIN'),
				'PERMISSIONS' => array(
					Permissions::ENTITY_LINES => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_ANY,
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_CONNECTORS => array(
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_ALLOW,
					),
					Permissions::ENTITY_SESSION => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_HISTORY => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_JOIN => array(
						Permissions::ACTION_PERFORM => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_VOTE_HEAD => array(
						Permissions::ACTION_PERFORM => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_SETTINGS => array(
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_ALLOW,
					)
				)
			),
			'CHIEF' => array(
				'NAME' => GetMessage('IMOL_ROLE_CHIEF'),
				'PERMISSIONS' => array(
					Permissions::ENTITY_LINES => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_ANY,
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_CONNECTORS => array(
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_SESSION => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_HISTORY => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_JOIN => array(
						Permissions::ACTION_PERFORM => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_VOTE_HEAD => array(
						Permissions::ACTION_PERFORM => Permissions::PERMISSION_ANY,
					),
					Permissions::ENTITY_SETTINGS => array(
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_NONE,
					)
				)
			),
			'MANAGER' => array(
				'NAME' => GetMessage('IMOL_ROLE_MANAGER'),
				'PERMISSIONS' => array(
					Permissions::ENTITY_LINES => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_ANY,
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_NONE,
					),
					Permissions::ENTITY_CONNECTORS => array(
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_ALLOW,
					),
					Permissions::ENTITY_SESSION => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_SELF,
					),
					Permissions::ENTITY_HISTORY => array(
						Permissions::ACTION_VIEW => Permissions::PERMISSION_SELF,
					),
					Permissions::ENTITY_JOIN => array(
						Permissions::ACTION_PERFORM => Permissions::PERMISSION_SELF,
					),
					Permissions::ENTITY_VOTE_HEAD => array(
						Permissions::ACTION_PERFORM => Permissions::PERMISSION_NONE,
					),
					Permissions::ENTITY_SETTINGS => array(
						Permissions::ACTION_MODIFY => Permissions::PERMISSION_NONE,
					)
				)
			)
		);

		$roleIds = array();
		foreach ($defaultRoles as $roleCode => $role)
		{
			$addResult = \Bitrix\ImOpenlines\Model\RoleTable::add(array(
				'NAME' => $role['NAME'],
				'XML_ID' => $roleCode,
			));

			$roleId = $addResult->getId();
			if ($roleId)
			{
				$roleIds[$roleCode] = $roleId;
				\Bitrix\ImOpenlines\Security\RoleManager::setRolePermissions($roleId, $role['PERMISSIONS']);
			}
		}

		if (isset($roleIds['ADMIN']))
		{
			\Bitrix\ImOpenlines\Model\RoleAccessTable::add(array(
				'ROLE_ID' => $roleIds['ADMIN'],
				'ACCESS_CODE' => 'G1'
			));
		}
		if (isset($roleIds['CHIEF']))
		{
			$dbGroup = \CGroup::GetList($by = "", $order = "", Array("STRING_ID" => "DIRECTION"));
			if($arGroup = $dbGroup->Fetch())
			{
				\Bitrix\ImOpenlines\Model\RoleAccessTable::add(array(
					'ROLE_ID' => $roleIds['CHIEF'],
					'ACCESS_CODE' => 'G'.$arGroup["ID"]
				));
			}
		}

		if (isset($roleIds['MANAGER']) && \Bitrix\Main\Loader::includeModule('intranet'))
		{
			$departmentTree = \CIntranetUtils::GetDeparmentsTree();
			$rootDepartment = (int)$departmentTree[0][0];

			if ($rootDepartment > 0)
			{
				\Bitrix\ImOpenlines\Model\RoleAccessTable::add(array(
					'ROLE_ID' => $roleIds['MANAGER'],
					'ACCESS_CODE' => 'DR'.$rootDepartment
				));
			}
		}
		
		return "";
	}
}